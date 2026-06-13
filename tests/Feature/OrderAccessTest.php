<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrder(array $attributes = []): Order
{
    return Order::create(array_merge([
        'order_number' => 'TNM-10001',
        'user_id' => null,
        'status' => 'pending',
        'source' => 'website',
        'customer_name' => 'Guest User',
        'customer_phone' => '01712345678',
        'shipping_address' => 'Dhaka, Bangladesh',
        'shipping_district' => 'Dhaka',
        'shipping_thana' => 'Dhanmondi',
        'shipping_cost' => 60,
        'subtotal' => 500,
        'discount_total' => 0,
        'total' => 560,
        'payment_status' => 'unpaid',
        'payment_method' => 'cod',
    ], $attributes));
}

test('guest users can not open another order success page directly', function () {
    $order = makeOrder();

    $response = $this->get(route('order.success', $order->order_number));

    $response->assertRedirect(route('order.track'));
});

test('guest users can open order success page when session access exists', function () {
    $order = makeOrder();

    $response = $this
        ->withSession(['order_access_numbers' => [$order->order_number]])
        ->get(route('order.success', $order->order_number));

    $response->assertOk();
});

test('authenticated owners can open their own order success page', function () {
    $user = User::factory()->create();
    $order = makeOrder([
        'order_number' => 'TNM-10002',
        'user_id' => $user->id,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('order.success', $order->order_number));

    $response->assertOk();
});

test('tracking by phone number no longer reveals orders', function () {
    $order = makeOrder([
        'order_number' => 'TNM-10003',
        'customer_phone' => '01799999999',
    ]);

    $response = $this->get(route('order.track', ['track_input' => $order->customer_phone]));

    $response->assertOk();
    $response->assertDontSee($order->order_number);
});

test('tracking by order number still works', function () {
    $order = makeOrder([
        'order_number' => 'TNM-10004',
    ]);

    $response = $this->get(route('order.track', ['track_input' => $order->order_number]));

    $response->assertOk();
    $response->assertSee($order->order_number);
});
