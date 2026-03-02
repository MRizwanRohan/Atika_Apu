<?php

namespace App\Http\Controllers\Admin\MetaTracking;

use App\Http\Controllers\Controller;
use App\Jobs\SendMetaConversionEvent;
use App\Models\MetaEventLog;
use App\Models\Order;
use App\Models\TrackingSettings;
use App\Services\MetaConversion\MetaConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MetaTrackingController extends Controller
{
    public function __construct(private MetaConversionService $metaService) {}

    // ──────────────────────────────────────────────
    // SETTINGS
    // ──────────────────────────────────────────────

    /**
     * Show tracking settings page.
     */
    public function settingsIndex(): Response
    {
        $settings = TrackingSettings::getSettings();

        return Inertia::render('Admin/MetaTracking/Settings', [
            'settings' => [
                'id'                 => $settings->id,
                'pixel_id'           => $settings->pixel_id,
                'access_token'       => $settings->getRawAccessToken(), // show in admin only
                'test_event_code'    => $settings->test_event_code,
                'api_version'        => $settings->api_version,
                'pixel_enabled'      => $settings->pixel_enabled,
                'capi_enabled'       => $settings->capi_enabled,
                'test_mode'          => $settings->test_mode,
                'purchase_fire_mode' => $settings->purchase_fire_mode,
            ],
        ]);
    }

    /**
     * Save tracking settings.
     */
    public function settingsUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pixel_id'           => 'nullable|string|max:50',
            'access_token'       => 'nullable|string|max:500',
            'test_event_code'    => 'nullable|string|max:50',
            'api_version'        => 'nullable|string|max:10',
            'pixel_enabled'      => 'boolean',
            'capi_enabled'       => 'boolean',
            'test_mode'          => 'boolean',
            'purchase_fire_mode' => 'required|in:on_order_created,on_confirmed,on_delivered,manual_only,disabled',
        ]);

        $settings = TrackingSettings::getSettings();
        $settings->update($validated);
        TrackingSettings::clearCache();

        return response()->json(['message' => 'Tracking settings saved successfully.']);
    }

    /**
     * Send a test PageView event to validate configuration.
     */
    public function sendTestEvent(): JsonResponse
    {
        $result = $this->metaService->sendTestEvent();

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    // ──────────────────────────────────────────────
    // EVENT LOGS
    // ──────────────────────────────────────────────

    /**
     * Show the event logs page.
     */
    public function logsIndex(Request $request): Response
    {
        $query = MetaEventLog::with('order:id,order_number,customer_name,status')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('event_name')) {
            $query->where('event_name', $request->event_name);
        }

        if ($request->filled('order_number')) {
            $query->whereHas('order', fn ($q) =>
                $q->where('order_number', 'like', '%' . $request->order_number . '%')
            );
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/MetaTracking/EventLogs', [
            'logs'    => $logs,
            'filters' => $request->only(['status', 'event_name', 'order_number']),
        ]);
    }

    // ──────────────────────────────────────────────
    // MANUAL FIRE
    // ──────────────────────────────────────────────

    /**
     * Manually fire a Purchase event for a specific order.
     */
    public function manualFire(Request $request, Order $order): JsonResponse
    {
        if (MetaEventLog::alreadySent($order->id, 'Purchase')) {
            $existingLog = MetaEventLog::getForOrder($order->id, 'Purchase');
            return response()->json([
                'success' => false,
                'message' => 'Purchase event already sent for this order.',
                'log'     => $existingLog,
            ], 409);
        }

        $result = $this->metaService->sendPurchaseEvent($order, 'manual');

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Bulk fire Purchase events for multiple orders, skipping already-sent ones.
     */
    public function bulkFire(Request $request): JsonResponse
    {
        $request->validate([
            'order_ids'   => 'required|array|min:1|max:100',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orderIds = $request->order_ids;

        // Determine which ones are already sent — skip them
        $alreadySentIds = MetaEventLog::whereIn('order_id', $orderIds)
            ->where('event_name', 'Purchase')
            ->where('status', 'sent')
            ->pluck('order_id')
            ->toArray();

        $toFireIds = array_values(array_diff($orderIds, $alreadySentIds));

        $sent    = [];
        $skipped = array_values($alreadySentIds);
        $failed  = [];

        foreach ($toFireIds as $orderId) {
            $order  = Order::with('items.product', 'items.productVariation')->find($orderId);
            if (!$order) continue;

            $result = $this->metaService->sendPurchaseEvent($order, 'manual');

            if ($result['success']) {
                $sent[] = $orderId;
            } else {
                $failed[] = ['order_id' => $orderId, 'reason' => $result['message']];
            }
        }

        return response()->json([
            'success'      => true,
            'sent'         => $sent,
            'skipped'      => $skipped,
            'failed'       => $failed,
            'sent_count'   => count($sent),
            'skipped_count'=> count($skipped),
            'failed_count' => count($failed),
        ]);
    }

    /**
     * Resend (force retry) a Purchase event for a specific order.
     */
    public function resend(Order $order): JsonResponse
    {
        $result = $this->metaService->sendPurchaseEvent($order, 'manual', forceResend: true);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Get the Meta event status for a single order (for the order table badge).
     */
    public function orderStatus(Order $order): JsonResponse
    {
        $log = MetaEventLog::getForOrder($order->id, 'Purchase');

        return response()->json([
            'sent'    => (bool) $log && $log->status === 'sent',
            'status'  => $log?->status,
            'sent_at' => $log?->sent_at?->toDateTimeString(),
            'event_id'=> $log?->event_id,
            'log'     => $log,
        ]);
    }

    /**
     * Get the pixel settings to expose to frontend (no access token).
     */
    public function pixelConfig(): JsonResponse
    {
        $settings = TrackingSettings::getSettings();

        return response()->json([
            'pixel_id'      => $settings->pixel_enabled ? $settings->pixel_id : null,
            'pixel_enabled' => $settings->pixel_enabled,
            'test_mode'     => $settings->test_mode,
        ]);
    }
}
