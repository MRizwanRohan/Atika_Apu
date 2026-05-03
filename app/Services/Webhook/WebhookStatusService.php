<?php

namespace App\Services\Webhook;

use App\Models\Order;
use App\Models\WebhookLog;
use App\Models\CourierSetting;
use App\Services\Order\OrderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class WebhookStatusService
{
    private OrderInterface $orderService;

    public function __construct(OrderInterface $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Process a Pathao webhook event
     */
    public function processPathaoWebhook(array $payload): void
    {
        $eventKey = $payload['event'] ?? '';
        $orderStatus = $payload['order_status'] ?? '';
        $orderStatusSlug = $payload['order_status_slug'] ?? '';
        
        $eventType = !empty($eventKey) ? $eventKey : 'unknown';

        // Handle integration test event
        if ($eventType === 'webhook_integration') {
            $this->logWebhook('pathao', null, $eventType, $payload, 'processed');
            return;
        }

        $consignmentId = $payload['consignment_id'] ?? null;
        $merchantOrderId = $payload['merchant_order_id'] ?? null;

        // Find order
        $order = $this->findOrder($consignmentId, $merchantOrderId);

        if (!$order) {
            $this->logWebhook('pathao', null, $eventType, $payload, 'failed', 'Order not found');
            Log::warning('Pathao webhook: Order not found', [
                'consignment_id' => $consignmentId,
                'merchant_order_id' => $merchantOrderId,
            ]);
            return;
        }

        // Get status mapping from settings
        $settings = CourierSetting::first();
        $mapping = $settings->pathao_status_mapping ?? $this->getDefaultPathaoMapping();

        /**
         * 🔍 Robust Status Matching
         * Try all possible Pathao status strings against the mapping keys:
         * 1. order.event-name (event)
         * 2. Event Name (order_status)
         * 3. Event_Name (order_status_slug)
         */
        $mappedStatus = 'no_action';
        
        if (isset($mapping[$eventKey])) {
            $mappedStatus = $mapping[$eventKey];
        } elseif (isset($mapping[$orderStatus])) {
            $mappedStatus = $mapping[$orderStatus];
        } elseif (isset($mapping[$orderStatusSlug])) {
            $mappedStatus = $mapping[$orderStatusSlug];
        }

        $this->applyStatusUpdate($order, $mappedStatus, $orderStatus ?: $eventKey, 'pathao', $payload);
    }

    /**
     * Process a Steadfast webhook event
     */
    public function processSteadfastWebhook(array $payload): void
    {
        $notificationType = $payload['notification_type'] ?? 'unknown';
        $consignmentId = $payload['consignment_id'] ?? null;
        $invoice = $payload['invoice'] ?? null;
        $courierStatus = $payload['status'] ?? null;

        // For tracking_update, just log and return
        if ($notificationType === 'tracking_update') {
            $order = $this->findOrder($consignmentId, $invoice);
            $this->logWebhook('steadfast', $order?->id, $notificationType, $payload, 'processed');

            // Update delivery_status with tracking message
            if ($order && isset($payload['tracking_message'])) {
                $order->update(['delivery_status' => $payload['tracking_message']]);
            }
            return;
        }

        // For delivery_status notifications
        if ($notificationType !== 'delivery_status' || !$courierStatus) {
            $this->logWebhook('steadfast', null, $notificationType, $payload, 'failed', 'Invalid notification type or missing status');
            return;
        }

        $order = $this->findOrder($consignmentId, $invoice);

        if (!$order) {
            $this->logWebhook('steadfast', null, $notificationType, $payload, 'failed', 'Order not found');
            Log::warning('Steadfast webhook: Order not found', [
                'consignment_id' => $consignmentId,
                'invoice' => $invoice,
            ]);
            return;
        }

        // Get status mapping from settings
        $settings = CourierSetting::first();
        $mapping = $settings->steadfast_status_mapping ?? $this->getDefaultSteadfastMapping();

        $mappedStatus = $mapping[$courierStatus] ?? 'no_action';

        $this->applyStatusUpdate($order, $mappedStatus, $courierStatus, 'steadfast', $payload);
    }

    /**
     * Apply mapped status to the order using OrderService for stock safety
     *
     * ⚠️ CRITICAL: MUST use OrderService::updateOrderStatus() — NOT direct $order->update()
     * This ensures stock is properly restored on cancelled/returned transitions
     */
    private function applyStatusUpdate(Order $order, string $mappedStatus, string $rawStatus, string $courier, array $payload): void
    {
        try {
            DB::beginTransaction();

            // Extract collected amount from payload (varies by courier)
            $collectedAmount = 0;
            if ($courier === 'pathao') {
                $collectedAmount = $payload['collected_amount'] ?? 0;
            } elseif ($courier === 'steadfast') {
                $collectedAmount = $payload['cod_amount'] ?? 0;
            }

            // Always update raw delivery_status from courier
            $order->update(['delivery_status' => $rawStatus]);

            if ($mappedStatus === 'no_action' || empty($mappedStatus)) {
                $this->logWebhook($courier, $order->id, $rawStatus, $payload, 'processed');
                DB::commit();
                return;
            }

            // Skip if same status (only if amount matches too, but status check is enough for now)
            if ($order->status === $mappedStatus && $order->paid_amount == $collectedAmount) {
                $this->logWebhook($courier, $order->id, $rawStatus, $payload, 'processed');
                DB::commit();
                return;
            }

            /**
             * Special case: partially_delivered
             * Store the collected amount and set status
             */
            if ($mappedStatus === 'partially_delivered') {
                $order->update([
                    'status' => 'partially_delivered',
                    'paid_amount' => $collectedAmount,
                    'payment_status' => 'partial',
                ]);
                
                Log::info("Webhook: Order #{$order->id} marked as partially delivered", [
                    'courier' => $courier,
                    'collected' => $collectedAmount
                ]);

                $this->logWebhook($courier, $order->id, $rawStatus, $payload, 'processed');
                DB::commit();
                return;
            }

            /**
             * ⚡ Use OrderService::updateOrderStatus() for ALL other status changes
             * This handles stock restoration safely.
             */
            $this->orderService->updateOrderStatus($order->id, $mappedStatus);

            // If status is delivered, update payment details from courier payload
            if ($mappedStatus === 'delivered') {
                $order->fresh()->update([
                    'payment_status' => 'paid',
                    'paid_amount' => $collectedAmount > 0 ? $collectedAmount : $order->fresh()->total,
                ]);
            }

            Log::info("Webhook: Order #{$order->id} status updated", [
                'courier' => $courier,
                'raw_status' => $rawStatus,
                'mapped_status' => $mappedStatus,
                'old_status' => $order->status,
            ]);

            $this->logWebhook($courier, $order->id, $rawStatus, $payload, 'processed');
            DB::commit();

            // Invalidate order cache
            Order::incrementCacheVersion();
        } catch (\InvalidArgumentException $e) {
            DB::rollBack();
            // Status transition not allowed (e.g. delivered → cancelled is blocked by OrderService)
            Log::warning("Webhook: Status transition blocked", [
                'order_id' => $order->id,
                'courier' => $courier,
                'attempted_status' => $mappedStatus,
                'error' => $e->getMessage(),
            ]);
            $this->logWebhook($courier, $order->id, $rawStatus, $payload, 'failed', $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Webhook: Failed to update order status", [
                'order_id' => $order->id,
                'courier' => $courier,
                'error' => $e->getMessage(),
            ]);
            $this->logWebhook($courier, $order->id, $rawStatus, $payload, 'failed', $e->getMessage());
        }
    }

    /**
     * Find order by consignment_id (tracking_number) or invoice/merchant_order_id (order_number)
     */
    private function findOrder(?string $consignmentId, ?string $invoiceOrMerchantId): ?Order
    {
        if ($consignmentId) {
            // Try tracking_number first (Pathao stores consignment_id here)
            $order = Order::where('tracking_number', $consignmentId)->first();
            if ($order) return $order;

            // Also try consignment_id field (Steadfast)
            $order = Order::where('consignment_id', $consignmentId)->first();
            if ($order) return $order;
        }

        if ($invoiceOrMerchantId) {
            return Order::where('order_number', $invoiceOrMerchantId)->first();
        }

        return null;
    }

    /**
     * Log webhook event to database
     */
    private function logWebhook(string $courier, ?int $orderId, string $eventType, array $payload, string $status, ?string $errorMessage = null): void
    {
        try {
            WebhookLog::create([
                'courier' => $courier,
                'order_id' => $orderId,
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => $status,
                'error_message' => $errorMessage,
                'processed_at' => $status === 'processed' ? now() : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log webhook', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Default Pathao event → order status mapping
     */
    public function getDefaultPathaoMapping(): array
    {
        return [
            // Human readable keys
            'Delivered' => 'delivered',
            'Pickup Cancelled' => 'cancelled',
            'Returned' => 'returned',
            'Order Created' => 'processing',
            'Assigned For Pickup' => 'shipped',
            'Pickup' => 'shipped',
            'Pickup Failed' => 'on_hold',
            'In Transit' => 'shipped',
            'Assigned for Delivery' => 'shipped',
            'Partial Delivery' => 'partially_delivered',
            'Return' => 'returned',
            'Delivery Failed' => 'on_hold',
            'On Hold' => 'on_hold',
            'Payment Invoice' => 'no_action',
            'Paid Return' => 'returned',
            'Exchange' => 'no_action',
            'Order Updated' => 'no_action',
            'Pickup Requested' => 'processing',
            'At the Sorting Hub' => 'shipped',
            'Received at Last Mile Hub' => 'shipped',

            // Dot notation keys (Pathao slug format)
            'order.delivered' => 'delivered',
            'order.pickup-cancelled' => 'cancelled',
            'order.returned' => 'returned',
            'order.created' => 'processing',
            'order.assigned-for-pickup' => 'shipped',
            'order.pickup' => 'shipped',
            'order.pickup-failed' => 'on_hold',
            'order.in-transit' => 'shipped',
            'order.assigned-for-delivery' => 'shipped',
            'order.partial-delivery' => 'partially_delivered',
            'order.return' => 'returned',
            'order.delivery-failed' => 'on_hold',
            'order.on-hold' => 'on_hold',
        ];
    }

    /**
     * Default Steadfast status → order status mapping
     */
    public function getDefaultSteadfastMapping(): array
    {
        return [
            'pending' => 'processing',
            'delivered' => 'delivered',
            'partial_delivered' => 'partially_delivered',
            'cancelled' => 'cancelled',
            'unknown' => 'no_action',
        ];
    }
}
