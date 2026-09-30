<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\TrackingSettings;
use App\Services\MetaConversion\MetaConversionService;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;


class SendMetaConversionOnOrderCreated implements ShouldHandleEventsAfterCommit
{
    public function __construct(private MetaConversionService $metaService) {}

    public function handle(OrderCreated $event): void
    {
        $settings = TrackingSettings::getSettings();

        if (!$settings->capi_enabled) {
            return;
        }

        if ($settings->purchase_fire_mode !== 'on_order_created') {
            return;
        }

        // Don't fire for incomplete/cancelled orders
        if (in_array($event->order->status, ['incomplete', 'cancelled'])) {
            return;
        }

        // Call directly — no queue dependency, always fires immediately
        try {
            $this->metaService->sendPurchaseEvent($event->order, 'on_order_created');
        } catch (\Throwable $e) {
            Log::error('SendMetaConversionOnOrderCreated failed', [
                'order_id' => $event->order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
