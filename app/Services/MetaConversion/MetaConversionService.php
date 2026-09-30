<?php

namespace App\Services\MetaConversion;

use App\Contracts\MetaConversionInterface;
use App\DTOs\MetaEventDTO;
use App\Models\MetaEventLog;
use App\Models\Order;
use App\Models\TrackingSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaConversionService implements MetaConversionInterface
{
    private const GRAPH_API_BASE = 'https://graph.facebook.com';

    /**
     * Generate a deterministic, deduplication-safe event ID.
     * Same order + event name = same ID for the lifetime of the order.
     */
    public function generateEventId(string $orderNumber, string $eventName): string
    {
        return hash('sha256', $orderNumber . '_' . $eventName);
    }

    /**
     * Generate a fresh unique event ID (for events without an order).
     */
    public function generateUniqueEventId(string $eventName): string
    {
        return hash('sha256', $eventName . Str::uuid()->toString() . microtime());
    }

    /**
     * Determine whether an automatic Purchase is allowed for this order.
     */
    public function shouldFirePurchase(Order $order, string $trigger): bool
    {
        $settings = TrackingSettings::getSettings();

        if (!$settings->capi_enabled || !$this->isEligibleOrder($order)
            || in_array($settings->purchase_fire_mode, ['disabled', 'manual_only'], true)
            || $settings->purchase_fire_mode !== $trigger) {
            return false;
        }

        return match ($trigger) {
            'on_order_created' => true,
            'on_confirmed' => $order->status === 'confirmed',
            'on_delivered' => $order->status === 'delivered',
            default => false,
        };
    }

    private function isEligibleOrder(Order $order): bool
    {
        return $order->exists
            && !in_array($order->status, ['incomplete', 'cancelled', 'returned'], true)
            && $order->payment_status !== 'refunded'
            && is_numeric($order->total)
            && is_finite((float) $order->total)
            && (float) $order->total > 0;
    }

    /**
     * Send once per order. Failed attempts reuse their original identity and
     * timestamp; forceResend is retained for callers, never to replay a success.
     */
    public function sendPurchaseEvent(Order $order, string $trigger = 'system', bool $forceResend = false): array
    {
        $settings = TrackingSettings::getSettings();
        $skip = fn (string $message, ?string $eventId = null) => [
            'success' => false,
            'message' => $message,
            'event_id' => $eventId,
            'response' => null,
            'retryable' => false,
        ];

        if (!$settings->capi_enabled || !$settings->pixel_id || !$settings->getRawAccessToken()) {
            return $skip('CAPI is disabled or not configured.');
        }

        // All automatic/manual/queue paths serialize on the same persisted
        // order, including the very first send when no event log exists yet.
        return DB::transaction(function () use ($order, $trigger, $settings, $skip) {
            $order = Order::whereKey($order->id)->lockForUpdate()->first();
            if (!$order || !$this->isEligibleOrder($order)) {
                return $skip('Order is not eligible for a Purchase event.');
            }

            if ($settings->purchase_fire_mode === 'disabled'
                || ($trigger !== 'manual' && !$this->shouldFirePurchase($order, $trigger))) {
                return $skip('Purchase tracking is disabled for this trigger.');
            }

            if (MetaEventLog::alreadySent($order->id, 'Purchase')) {
                return $skip('Purchase event already sent for this order.',
                    MetaEventLog::getForOrder($order->id, 'Purchase')?->event_id);
            }

            $log = MetaEventLog::getForOrder($order->id, 'Purchase');
            $eventId = $log?->event_id ?? $this->generateEventId($order->order_number, 'Purchase');

            if ($log?->payload) {
                // Do not change the original event time, value or customer
                // attribution after a timeout: Meta may already have accepted it.
                $payload = $log->payload;
            } else {
                $dto = MetaEventDTO::fromOrder($order, $eventId);
                $context = $order->meta_tracking_context ?? [];
                $dto->clientIpAddress = $context['client_ip_address'] ?? null;
                $dto->clientUserAgent = $context['client_user_agent'] ?? null;
                $dto->fbc = $context['fbc'] ?? null;
                $dto->fbp = $context['fbp'] ?? null;
                $dto->eventSourceUrl = route('order.success', ['order' => $order->order_number]);
                $payload = $dto->toMetaPayload();
            }

            if (!is_numeric($payload['custom_data']['value'] ?? null)
                || !is_finite((float) $payload['custom_data']['value'])
                || (float) $payload['custom_data']['value'] <= 0
                || ($payload['custom_data']['currency'] ?? null) !== 'BDT') {
                return $skip('Purchase payload has invalid value or currency.', $eventId);
            }

            // An expired uncertain attempt must not be turned into a new sale.
            if (($payload['event_time'] ?? 0) < now()->subDays(7)->timestamp) {
                return $skip('Purchase event is too old to retry.', $eventId);
            }

            $log ??= new MetaEventLog([
                'order_id' => $order->id,
                'event_name' => 'Purchase',
                'event_id' => $eventId,
                'trigger' => $trigger,
                'fired_by' => $trigger === 'manual' ? (string) (auth()->id() ?? 'admin') : 'system',
                'attempts' => 0,
            ]);
            $log->fill([
                'status' => 'pending',
                'payload' => $payload,
                'attempts' => min(255, $log->attempts + 1),
                'error_message' => null,
            ])->save();

            try {
                $response = $this->callMetaApi(
                    $settings->pixel_id,
                    $settings->getRawAccessToken(),
                    $settings->api_version,
                    [$payload],
                    $settings->test_mode ? $settings->test_event_code : null
                );
                $log->markSent($response);

                return [
                    'success' => true,
                    'message' => 'Purchase event sent successfully.',
                    'event_id' => $eventId,
                    'response' => $response,
                ];
            } catch (\Throwable $e) {
                $log->markFailed($e->getMessage());
                Log::error('MetaConversionService: Purchase event failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'message' => 'Failed: ' . $e->getMessage(),
                    'event_id' => $eventId,
                    'response' => null,
                    'retryable' => true,
                ];
            }
        });
    }

    /**
     * Send a test event to validate the Pixel + CAPI setup.
     */
    public function sendTestEvent(): array
    {
        $settings = TrackingSettings::getSettings();

        if (!$settings->pixel_id || !$settings->getRawAccessToken()) {
            return ['success' => false, 'message' => 'Pixel ID or Access Token is missing.'];
        }

        $payload = [
            'event_name'    => 'PageView',
            'event_id'      => $this->generateUniqueEventId('PageView'),
            'event_time'    => time(),
            'action_source' => 'website',
            'user_data'     => [
                'client_ip_address' => request()->ip(),
                'client_user_agent' => request()->userAgent(),
            ],
        ];

        try {
            $response = $this->callMetaApi(
                $settings->pixel_id,
                $settings->getRawAccessToken(),
                $settings->api_version,
                [$payload],
                $settings->test_event_code // Always use test code for test events
            );

            return ['success' => true, 'message' => 'Test event sent.', 'response' => $response];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Make the actual HTTP call to Meta Graph API.
     */
    private function callMetaApi(
        string $pixelId,
        string $accessToken,
        string $apiVersion,
        array $events,
        ?string $testEventCode = null
    ): array {
        $url = sprintf('%s/%s/%s/events', self::GRAPH_API_BASE, $apiVersion, $pixelId);

        $body = [
            'data'         => $events,
            'access_token' => $accessToken,
        ];

        if ($testEventCode) {
            $body['test_event_code'] = $testEventCode;
        }

        $response = Http::timeout(15)
            ->post($url, $body);

        if ($response->failed()) {
            $errorData = $response->json();
            $errorMsg  = $errorData['error']['message'] ?? $response->body();
            throw new \RuntimeException('Meta API error: ' . $errorMsg);
        }

        $result = $response->json();
        if (!is_array($result) || (int) ($result['events_received'] ?? 0) !== count($events)) {
            throw new \RuntimeException('Meta API did not acknowledge all events.');
        }

        return $result;
    }
}
