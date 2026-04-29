<?php

namespace App\Services\Order;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Order;

interface OrderInterface
{
    public function createOrder(array $data): Order;




    public function getOrders(array $params = []): LengthAwarePaginator;

    public function getStatusCounts(array $filters = []);

    // updateOrderStatus
    public function updateOrderStatus(int $orderId, string $status): Order;






    public function updateOrder(int $orderId, array $data): Order;

    public function updateCourierDetails(int $orderId, array $data): Order;

    public function deleteOrder(int $id);

    // updateNewOrder($orderId, $request->all());
    public function updateNewOrder(int $orderId, array $data): Order;

    public function processPartialDelivery(int $orderId, array $data): Order;

    public function calculateOrderTotals(Order $order): void;

    /**
     * Return admin-facing notifications (count + recent items)
     *
     * @return array
     */
    public function adminNotifications(): array;
} 
