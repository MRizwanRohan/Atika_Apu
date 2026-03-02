<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Models\MetaEventLog;
use App\Models\TrackingSettings;
use App\Services\MetaConversion\MetaConversionService;
use Illuminate\Support\Facades\Log;

class SendMetaConversionOnOrderUpdated
{
    public function __construct(private MetaConversionService $metaService) {}

    public function handle(OrderUpdated $event): void
    {
        $settings = TrackingSettings::getSettings();

        if (!$settings->capi_enabled) {
            return;
        }

        $order  = $event->order;
        $mode   = $settings->purchase_fire_mode;

        // Map statuses to fire modes
        $statusToMode = [
            'confirmed'  => 'on_confirmed',
            'delivered'  => 'on_delivered',
        ];

        $currentStatusMode = $statusToMode[$order->status] ?? null;

        if (!$currentStatusMode || $mode !== $currentStatusMode) {
            return;
        }

        // Skip if already sent
        if (MetaEventLog::alreadySent($order->id, 'Purchase')) {
            return;
        }

        // Call directly — no queue dependency, always fires immediately
        try {
            $this->metaService->sendPurchaseEvent($order, $currentStatusMode);
        } catch (\Throwable $e) {
            Log::error('SendMetaConversionOnOrderUpdated failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
