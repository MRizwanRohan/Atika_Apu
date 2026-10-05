<?php

namespace App\Http\Controllers\Admin\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Courier\CourierManager;
use Illuminate\Support\Facades\Log;

class ShipmentTrackingController extends Controller
{
    public function show(Order $order, CourierManager $courier)
    {
        if ($order->courier_name !== 'steadfast' || ! $order->consignment_id || $order->consignment_id === 'N/A') {
            return response()->json(['message' => 'A Steadfast consignment ID is required to track this shipment.'], 422);
        }
        try {
            $result = $courier->driver('steadfast')->getStatusByConsignmentId((string) $order->consignment_id);
            if ((int) ($result['status'] ?? 0) !== 200 || empty($result['delivery_status']) || ! is_string($result['delivery_status'])) {
                return response()->json(['message' => 'Steadfast could not return a delivery status. Please try again.'], 502);
            }
            $order->update(['delivery_status' => $result['delivery_status']]);
            Order::incrementCacheVersion();

            return response()->json(['status' => 200, 'delivery_status' => $result['delivery_status'], 'consignment_id' => $order->consignment_id]);
        } catch (\Throwable $e) {
            Log::warning('Steadfast tracking failed', ['order_id' => $order->id, 'exception' => get_class($e)]);

            return response()->json(['message' => 'Unable to fetch Steadfast status. Please try again.'], 502);
        }
    }
}
