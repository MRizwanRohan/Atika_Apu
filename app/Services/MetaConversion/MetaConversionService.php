<?php

namespace App\Services\MetaConversion;

use App\Contracts\MetaConversionInterface;
use App\DTOs\MetaEventDTO;
use App\Models\MetaEventLog;
use App\Models\Order;
use App\Models\TrackingSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaConversionService implements MetaConversionInterface
{
    private const GRAPH_API_BASE = 'https://graph.facebook.com';

    /**
     * Generate a deterministic, deduplication-safe event ID.
     * Same order + event name within the same minute = same ID.
     */
    public function generateEventId(string $orderNumber, string $eventName): string
    {
        $minuteSlot = date('YmdHi'); // floor to current minute
        return hash('sha256', $orderNumber . $eventName . $minuteSlot);
    }

    /**
     * Generate a fresh unique event ID (for events without an order).
     */
    public function generateUniqueEventId(string $eventName): string
    {
        return hash('sha256', $eventName . Str::uuid()->toString() . microtime());
    }

    /**
     * Determine if a Purchase event should fire given current settings + trigger.
     */
    public function shouldFirePurchase(Order $order, string $trigger): bool
    {
        $settings = TrackingSettings::getSettings();

        if (!$settings->capi_enabled) {
            return false;
        }

        if ($settings->purchase_fire_mode === 'disabled') {
            return false;
        }

        if ($settings->purchase_fire_mode === 'manual_only') {
            return false;
        }

        if ($settings->purchase_fire_mode !== $trigger) {
            return false;
        }

        return true;
    }

    /**
     * Send a Purchase event to Meta Conversion API.
     */
    public function sendPurchaseEvent(Order $order, string $trigger = 'system', bool $forceResend = false): array
    {
        $settings = TrackingSettings::getSettings();

        if (!$settings->capi_enabled || !$settings->pixel_id || !$settings->getRawAccessToken()) {
            return [
                'success' => false,
                'message' => 'CAPI is disabled or not configured.',
                'event_id' => null,
                'response' => null,
            ];
        }

        // Duplicate prevention
        if (!$forceResend && MetaEventLog::alreadySent($order->id, 'Purchase')) {
            return [
                'success' => false,
                'message' => 'Purchase event already sent for this order.',
                'event_id' => MetaEventLog::getForOrder($order->id, 'Purchase')?->event_id,
                'response' => null,
            ];
        }

        $eventId = $this->generateEventId($order->order_number, 'Purchase');

        // Build DTO
        $dto = MetaEventDTO::fromOrder($order, $eventId);

        // Attach server-side signals
        $dto->clientIpAddress = request()->ip();
        $dto->clientUserAgent = request()->userAgent();
        $dto->fbc = request()->cookie('_fbc');
        $dto->fbp = request()->cookie('_fbp');
        $dto->eventSourceUrl = config('app.url') . '/order/success/' . $order->order_number;

        $payload = $dto->toMetaPayload();

        // Create log entry BEFORE sending (captures payload)
        $log = MetaEventLog::create([
            'order_id'   => $order->id,
            'event_name' => 'Purchase',
            'event_id'   => $eventId,
            'trigger'    => $trigger,
            'status'     => 'pending',
            'payload'    => $payload,
            'fired_by'   => $trigger === 'manual' ? (auth()->id() ?? 'admin') : 'system',
            'attempts'   => 1,
        ]);

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
                'success'  => true,
                'message'  => 'Purchase event sent successfully.',
                'event_id' => $eventId,
                'response' => $response,
            ];
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());

            Log::error('MetaConversionService: Purchase event failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            return [
                'success'  => false,
                'message'  => 'Failed: ' . $e->getMessage(),
                'event_id' => $eventId,
                'response' => null,
            ];
        }
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

        return $response->json();
    }
}
