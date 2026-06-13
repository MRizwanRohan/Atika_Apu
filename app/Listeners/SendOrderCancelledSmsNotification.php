<?php

namespace App\Listeners;

use App\Events\OrderUpdated;
use App\Models\SmsSetting;
use App\Services\Sms\SmsService;
use Illuminate\Support\Facades\Log;

class SendOrderCancelledSmsNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(private SmsService $smsService) {}

    /**
     * Handle the event.
     */
    public function handle(OrderUpdated $event): void
    {
        $order = $event->order;

        try {
            // 1. Only send if status is cancelled
            if ($order->status !== 'cancelled') {
                return;
            }

            // 2. Prevent duplicate notifications on subsequent updates where status was not changed
            if (!$order->wasChanged('status')) {
                return;
            }

            // 3. Fetch SMS settings and verify gateways
            $smsSetting = SmsSetting::first();
            
            if ($smsSetting && $smsSetting->is_enabled && $smsSetting->order_cancel_sms_enabled) {
                
                // 4. Duplicate checks in log to prevent multiple sends on double triggers
                $alreadySent = \App\Models\SmsLog::where('phone', $order->customer_phone)
                    ->where('message', 'like', "%#{$order->order_number}%")
                    ->where('status', 'success')
                    ->exists();

                if ($alreadySent) {
                    return;
                }

                // 5. Build dynamic template parameters
                $message = str_replace(
                    ['{name}', '{order_number}', '{total}'],
                    [$order->customer_name, $order->order_number, $order->total],
                    $smsSetting->order_cancel_sms_template
                );

                // 6. Request SMS delivery
                $result = $this->smsService->sendSms($order->customer_phone, $message);

                // 7. Store SMS Logs
                \App\Models\SmsLog::create([
                    'customer_id' => $order->customer_id,
                    'phone' => $order->customer_phone,
                    'message' => $message,
                    'status' => $result['status'] ? 'success' : 'failed',
                    'error_message' => $result['status'] ? null : $result['message'],
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('SendOrderCancelledSmsNotification failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
