<?php

namespace Tests\Unit;

use App\Events\OrderCreated;
use App\Http\Controllers\Frontend\Order\OrderController;
use App\Listeners\SendMetaConversionOnOrderCreated;
use App\Models\MetaEventLog;
use App\Models\Order;
use App\Models\TrackingSettings;
use App\Services\Cart\CartService;
use App\Services\MetaConversion\MetaConversionService;
use App\Services\Order\OrderInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MetaConversionTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Never connect to the developer/production database. The application
        // has unrelated MySQL-only migrations, so build the tracking fixture.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'session.driver' => 'array']);
        DB::purge('sqlite');
        Cache::flush();
        Http::preventStrayRequests();

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->decimal('total', 12, 2);
            foreach (['customer_email', 'customer_name', 'customer_phone'] as $field) {
                $table->string($field)->nullable();
            }
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->unsignedBigInteger('product_variation_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
        });
        foreach ([
            '2026_03_02_000001_create_tracking_settings_table.php',
            '2026_03_02_000002_create_meta_event_logs_table.php',
            '2026_09_30_000001_add_meta_tracking_context_to_orders_table.php',
        ] as $migration) {
            (require database_path('migrations/' . $migration))->up();
        }
        TrackingSettings::create([
            'pixel_id' => '123456789', 'access_token' => 'test-token',
            'api_version' => 'v22.0', 'capi_enabled' => true, 'pixel_enabled' => true,
            'purchase_fire_mode' => 'on_order_created',
        ]);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1])]);
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'order_number' => 'TEST-' . (Order::count() + 1),
            'status' => 'pending', 'payment_status' => 'unpaid', 'total' => 200,
            'customer_name' => 'Test Buyer', 'customer_phone' => '01712345678',
        ], $attributes));
    }

    private function mode(string $mode): void
    {
        TrackingSettings::first()->update(['purchase_fire_mode' => $mode]);
        TrackingSettings::clearCache();
    }

    public function test_purchase_has_exact_value_currency_identity_and_customer_attribution(): void
    {
        $order = $this->order(['meta_tracking_context' => [
            'client_ip_address' => '192.0.2.10', 'client_user_agent' => 'CustomerBrowser',
            'fbp' => 'fb.1.1700000000000.12345', 'fbc' => 'fb.1.1700000000000.test-click',
        ]]);
        $service = app(MetaConversionService::class);
        $result = $service->sendPurchaseEvent($order, 'on_order_created');
        $this->assertTrue($result['success']);
        Http::assertSent(function ($request) use ($order, $service) {
            $event = $request['data'][0];
            return $event['event_id'] === $service->generateEventId($order->order_number, 'Purchase')
                && $event['custom_data']['value'] === 200.0
                && $event['custom_data']['currency'] === 'BDT'
                && $event['user_data']['client_user_agent'] === 'CustomerBrowser'
                && $event['user_data']['client_ip_address'] === '192.0.2.10'
                && $event['user_data']['fbp'] === 'fb.1.1700000000000.12345'
                && $event['user_data']['ph'] === hash('sha256', '8801712345678')
                && str_contains($event['event_source_url'], $order->order_number);
        });
        $this->assertArrayNotHasKey('meta_tracking_context', $order->toArray());
        $this->assertSame(1, MetaEventLog::first()->attempts);
    }

    public function test_success_cannot_be_replayed_by_manual_or_forced_resend_even_days_later(): void
    {
        $order = $this->order();
        $service = app(MetaConversionService::class);
        $this->assertTrue($service->sendPurchaseEvent($order, 'on_order_created')['success']);
        $this->travel(10)->days();
        $this->assertFalse($service->sendPurchaseEvent($order, 'manual', true)['success']);
        $this->assertFalse($service->sendPurchaseEvent($order, 'on_order_created')['success']);
        Http::assertSentCount(1);
        $this->assertSame(1, MetaEventLog::count());
    }

    public function test_failed_retry_reuses_log_payload_time_and_event_id(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['graph.facebook.com/*' => Http::sequence()
            ->push(['error' => ['message' => 'Temporary failure']], 500)
            ->push(['events_received' => 1])]);
        $order = $this->order();
        $service = app(MetaConversionService::class);
        $this->assertFalse($service->sendPurchaseEvent($order, 'on_order_created')['success']);
        $first = MetaEventLog::first();
        $payload = $first->payload;
        $this->assertSame(1, $first->attempts);
        $order->update(['total' => 1100]);
        $this->travel(5)->minutes();
        $this->assertTrue($service->sendPurchaseEvent($order, 'manual', true)['success']);
        $this->assertSame(1, MetaEventLog::count());
        $this->assertSame($payload, $first->fresh()->payload);
        $this->assertSame(2, $first->fresh()->attempts);
        $this->assertNull($first->fresh()->error_message);
        Http::assertSentCount(2);
    }

    public function test_invalid_orders_and_wrong_trigger_do_not_send(): void
    {
        $service = app(MetaConversionService::class);
        foreach ([
            ['status' => 'incomplete'], ['status' => 'cancelled'], ['status' => 'returned'],
            ['payment_status' => 'refunded'], ['total' => 0], ['total' => -1],
        ] as $attributes) {
            $this->assertFalse($service->sendPurchaseEvent($this->order($attributes), 'manual')['success']);
        }
        $this->mode('on_confirmed');
        $order = $this->order();
        $this->assertFalse($service->sendPurchaseEvent($order, 'on_order_created')['success']);
        $this->assertFalse($service->sendPurchaseEvent($order, 'on_confirmed')['success']);
        $this->mode('disabled');
        $this->assertFalse($service->sendPurchaseEvent($order, 'manual', true)['success']);
        Http::assertNothingSent();
    }

    public function test_confirmation_uses_saved_customer_and_never_admin_signals(): void
    {
        $this->mode('on_confirmed');
        $order = $this->order(['status' => 'confirmed']);
        $request = Request::create('/admin/orders', 'POST', [], ['_fbp' => 'admin-cookie'], [],
            ['REMOTE_ADDR' => '192.0.2.99', 'HTTP_USER_AGENT' => 'AdminBrowser']);
        $this->app->instance('request', $request);
        $this->assertTrue(app(MetaConversionService::class)->sendPurchaseEvent($order, 'on_confirmed')['success']);
        $user = MetaEventLog::first()->payload['user_data'];
        foreach (['client_ip_address', 'client_user_agent', 'fbp', 'fbc'] as $field) {
            $this->assertArrayNotHasKey($field, $user);
        }
    }

    public function test_unacknowledged_api_response_is_not_marked_sent(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 0])]);
        $this->assertFalse(app(MetaConversionService::class)
            ->sendPurchaseEvent($this->order(), 'on_order_created')['success']);
        $this->assertSame('failed', MetaEventLog::first()->status);
    }

    public function test_expired_failed_event_is_not_retimestamped_as_a_new_purchase(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'fail']], 500)]);
        $order = $this->order();
        $service = app(MetaConversionService::class);
        $this->assertFalse($service->sendPurchaseEvent($order, 'on_order_created')['success']);
        $this->assertSame('failed', MetaEventLog::first()->status);
        $this->travel(8)->days();
        $result = $service->sendPurchaseEvent($order, 'manual', true);
        $this->assertFalse($result['success']);
        $this->assertFalse($result['retryable']);
        Http::assertSentCount(1);
    }

    public function test_purchase_is_sent_only_after_commit_and_not_after_rollback(): void
    {
        Event::forget(OrderCreated::class);
        Event::listen(OrderCreated::class, SendMetaConversionOnOrderCreated::class);
        DB::beginTransaction();
        event(new OrderCreated($this->order()));
        Http::assertNothingSent();
        DB::rollBack();
        Http::assertNothingSent();

        DB::beginTransaction();
        event(new OrderCreated($this->order()));
        Http::assertNothingSent();
        DB::commit();
        Http::assertSentCount(1);
    }

    public function test_success_page_consumes_checkout_receipt_and_does_not_replay(): void
    {
        $order = $this->order();
        $request = Request::create('/order/success/' . $order->order_number);
        $request->headers->set('X-Inertia', 'true');
        $session = app('session')->driver();
        $request->setLaravelSession($session);
        $session->put('order_access_numbers', [$order->order_number]);
        $session->flash('meta_purchase_order', $order->order_number);
        $this->app->instance('request', $request);
        $controller = new OrderController(
            \Mockery::mock(OrderInterface::class),
            \Mockery::mock(CartService::class),
            app(MetaConversionService::class)
        );

        $first = $controller->success($order->order_number)->toResponse($request)->getData(true);
        $second = $controller->success($order->order_number)->toResponse($request)->getData(true);
        $this->assertTrue($first['props']['fire_pixel_now']);
        $this->assertSame(hash('sha256', $order->order_number . '_Purchase'), $first['props']['meta_event_id']);
        $this->assertFalse($second['props']['fire_pixel_now']);
        Http::assertNothingSent();
    }
}
