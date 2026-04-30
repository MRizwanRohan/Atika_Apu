<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\SmsSetting;
use App\Services\Sms\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendOrderSmsNotification 
{
    //use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct(private SmsService $smsService) {}

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;

        try {
            // Don't send SMS for incomplete or cancelled orders
            if (in_array($order->status, ['incomplete', 'cancelled'])) {
                return;
            }

            // Check settings
            $smsSetting = SmsSetting::first();
            
            if ($smsSetting && $smsSetting->is_enabled && $smsSetting->order_create_sms_enabled) {

                // Formatting message
                $message = str_replace(
                    ['{name}', '{order_number}', '{total}'],
                    [$order->customer_name, $order->order_number, $order->total],
                    $smsSetting->order_create_sms_template
                );

                // Sending SMS
                $result = $this->smsService->sendSms($order->customer_phone, $message);

                // Create log
                \App\Models\SmsLog::create([
                    'customer_id' => $order->customer_id, // This assumes Order has customer_id
                    'phone' => $order->customer_phone,
                    'message' => $message,
                    'status' => $result['status'] ? 'success' : 'failed',
                    'error_message' => $result['status'] ? null : $result['message'],
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('SendOrderSmsNotification failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
