<?php

namespace App\Contracts;

use App\Models\Order;

interface MetaConversionInterface
{
    /**
     * Send a Purchase event to Meta Conversion API.
     *
     * @param  Order  $order
     * @param  string $trigger  on_order_created|on_confirmed|on_delivered|manual
     * @param  bool   $forceResend  Skip duplicate check if true
     * @return array  { success, event_id, message, response }
     */
    public function sendPurchaseEvent(Order $order, string $trigger = 'system', bool $forceResend = false): array;

    /**
     * Check if a Purchase event should fire given settings + trigger.
     */
    public function shouldFirePurchase(Order $order, string $trigger): bool;

    /**
     * Send a test event to validate Pixel + CAPI configuration.
     */
    public function sendTestEvent(): array;
}
