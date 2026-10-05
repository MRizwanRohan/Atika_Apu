<?php

namespace Tests\Unit;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\GlobalOffer;
use App\Models\Product;
use App\Services\Offers\CheckoutPricingService;
use App\Services\Offers\GlobalOfferService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalOfferTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('global_offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('enabled')->default(true);
            $table->decimal('minimum_subtotal', 12, 2);
            $table->string('reward_type');
            $table->decimal('reward_value', 12, 2)->nullable();
            $table->decimal('maximum_discount', 12, 2)->nullable();
            $table->integer('priority')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function offer(array $data = []): GlobalOffer
    {
        return GlobalOffer::create(array_merge(['name' => 'Shopping reward', 'enabled' => true, 'minimum_subtotal' => 1000, 'reward_type' => 'percentage', 'reward_value' => 10], $data));
    }

    public function test_threshold_is_inclusive_and_revokes_when_cart_shrinks(): void
    {
        $this->offer();
        $service = new GlobalOfferService;
        $this->assertSame(0, $service->evaluate(999.99)['global_discount']);
        $this->assertSame(100, $service->evaluate(1000)['global_discount']);
        $this->assertSame(150, $service->evaluate(1500)['global_discount']);
        $below = $service->evaluate(900);
        $this->assertSame(0, $below['global_discount']);
        $this->assertSame(100, $below['next_milestone']['remaining']);
        $this->assertEquals(90, $below['next_milestone']['percent']);
    }

    public function test_best_monetary_reward_combines_with_free_delivery_once(): void
    {
        $this->offer();
        $fixed = $this->offer(['reward_type' => 'fixed', 'reward_value' => 200]);
        $this->offer(['reward_type' => 'free_delivery', 'reward_value' => null]);
        $this->offer(['reward_type' => 'free_delivery', 'reward_value' => null]);
        $result = (new GlobalOfferService)->evaluate(1500);
        $this->assertSame(200, $result['global_discount']);
        $this->assertTrue($result['free_delivery']);
        $this->assertCount(2, $result['applied_offers']);
        $this->assertContains($fixed->id, array_column($result['applied_offers'], 'id'));
    }

    public function test_coupon_wins_ties_and_larger_offer_replaces_coupon(): void
    {
        $this->offer();
        $service = new GlobalOfferService;
        $tie = $service->evaluate(1000, 100);
        $this->assertSame('coupon', $tie['discount_source']);
        $this->assertSame(0, $tie['global_discount']);
        $winner = $service->evaluate(1500, 100);
        $this->assertSame('global_offer', $winner['discount_source']);
        $this->assertSame(150, $winner['monetary_discount']);
        $this->assertSame(0, $winner['coupon_discount']);
        $this->assertSame('coupon', $service->evaluate(1500, 200)['discount_source']);
    }

    public function test_caps_fixed_rewards_empty_cart_and_priority(): void
    {
        $this->offer(['maximum_discount' => 120]);
        $winner = $this->offer(['reward_type' => 'fixed', 'reward_value' => 120, 'priority' => 10]);
        $service = new GlobalOfferService;
        $this->assertSame($winner->id, $service->evaluate(2000)['applied_offers'][0]['id']);
        $this->assertCount(0, $service->evaluate(0)['applied_offers']);
        $this->offer(['reward_type' => 'fixed', 'reward_value' => 10000]);
        $this->assertSame(1000, $service->evaluate(1000)['monetary_discount']);
        $this->assertSame(0, $service->evaluate(1000)['merchandise_payable']);
    }

    public function test_disabled_future_expired_and_archived_rules_are_excluded(): void
    {
        $this->offer(['enabled' => false]);
        $this->offer(['starts_at' => now()->addHour()]);
        $this->offer(['ends_at' => now()]);
        $this->offer()->delete();
        $active = $this->offer(['starts_at' => now(), 'ends_at' => now()->addHour()]);
        $this->assertSame([$active->id], (new GlobalOfferService)->activeOffers()->pluck('id')->all());
    }

    public function test_line_allocation_reconciles_paisa_without_overdiscounting(): void
    {
        $service = new CheckoutPricingService;
        $this->assertEquals([0.33, 0.34, 0.33], $service->allocate(1, [1, 1, 1]));
        $this->assertEquals([0, 10], $service->allocate(100, [0, 10]));
        $this->assertSame([], $service->allocate(100, []));
        $allocation = $service->allocate(123.45, [499.99, 700.01, 15]);
        $this->assertSame(12345, array_sum(array_map(fn ($amount) => (int) round($amount * 100), $allocation)));
    }

    public function test_unknown_shipping_is_not_a_fake_zero_and_free_delivery_handles_mixed_cart(): void
    {
        $cart = new Cart;
        $paid = new CartItem;
        $paid->setRelation('product', new Product(['is_free_delivery' => false]));
        $free = new CartItem;
        $free->setRelation('product', new Product(['is_free_delivery' => true]));
        $cart->setRelation('items', collect([$paid, $free]));
        $service = new CheckoutPricingService;
        $pricing = (new GlobalOfferService)->evaluate(1000);
        $quote = $service->withShipping($pricing, $cart, null, null);
        $this->assertNull($quote['grand_total']);
        $this->assertSame('address_required', $quote['shipping_quote_status']);
        $this->offer(['reward_type' => 'free_delivery', 'reward_value' => null]);
        $quote = $service->withShipping((new GlobalOfferService)->evaluate(1000), $cart, null, null);
        $this->assertSame(0, $quote['shipping_payable']);
        $this->assertSame(1000.0, $quote['grand_total']);
    }

    public function test_automatic_discount_rounds_to_whole_taka_before_total(): void
    {
        $this->offer();
        $pricing = (new GlobalOfferService)->evaluate(1485);
        $this->assertEquals(149, $pricing['global_discount']);
        $this->assertEquals(1336, $pricing['merchandise_payable']);
        $this->assertEquals(149, $pricing['applied_offers'][0]['saving']);
    }

    private function checkoutFixture(): array
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->integer('cart_id');
            $table->integer('product_id');
            $table->integer('product_variation_id')->nullable();
            $table->integer('quantity');
        });
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code');
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('pending');
            $table->integer('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_thana')->nullable();
            foreach (['shipping_cost', 'normal_shipping_charge', 'shipping_discount_total', 'global_discount_total', 'subtotal', 'discount_total', 'total', 'pos_discount'] as $field) {
                $table->decimal($field, 12, 2)->nullable();
            }
            $table->string('discount_type')->nullable();
            $table->json('offer_snapshot')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('product_id');
            $table->integer('product_variation_id')->nullable();
            $table->integer('quantity');
            foreach (['unit_price', 'subtotal', 'discount_amount', 'discount_total', 'final_price'] as $field) {
                $table->decimal($field, 12, 2)->default(0);
            }
            $table->string('discount_type')->nullable();
            $table->string('coupon_code')->nullable();
            $table->timestamps();
        });
        $product = new Product(['name' => 'Example', 'price' => 500, 'is_free_delivery' => false]);
        $product->id = 1;
        $product->setRelation('campaigns', collect());
        $line = new CartItem(['product_id' => 1, 'quantity' => 2, 'price' => 1]);
        $line->id = 1;
        $line->setRelation('product', $product);
        $line->setRelation('variation', null);
        $cart = new Cart(['session_id' => 'owned-session']);
        $cart->id = 1;
        $cart->setRelation('items', collect([$line]));
        DB::table('cart_items')->insert(['id' => 1, 'cart_id' => 1, 'product_id' => 1, 'quantity' => 2]);
        $order = \App\Models\Order::create(['session_id' => 'owned-session', 'status' => 'pending', 'shipping_cost' => 60]);
        \App\Models\OrderItem::create(['order_id' => $order->id, 'product_id' => 1, 'quantity' => 2, 'unit_price' => 500, 'subtotal' => 1000]);
        $cartService = \Mockery::mock(\App\Services\Cart\CartService::class);
        $cartService->shouldReceive('getCart')->with(null, 'owned-session')->andReturn($cart);
        $this->app->instance(\App\Services\Cart\CartService::class, $cartService);
        $request = \Illuminate\Http\Request::create('/order', 'POST');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);

        return [$order, $cart];
    }

    public function test_verified_quote_is_saved_once_and_remains_stable_after_rule_changes(): void
    {
        [$order] = $this->checkoutFixture();
        $offer = $this->offer();
        $this->offer(['reward_type' => 'free_delivery', 'reward_value' => null]);
        $service = $this->app->make(\App\Services\Order\OrderService::class);
        $method = new \ReflectionMethod($service, 'applyStorefrontPricing');
        DB::transaction(fn () => $method->invoke($service, $order, ['quote_total' => 900, 'items' => [['product_id' => 1, 'quantity' => 2]]]));
        $service->calculateOrderTotals($order);
        $this->assertEquals(900, $order->fresh()->total);
        $this->assertEquals(100, $order->fresh()->discount_total);
        $this->assertEquals(100, $order->fresh()->global_discount_total);
        $this->assertEquals(0, $order->fresh()->shipping_cost);
        $this->assertEquals(900, $order->items->first()->final_price);
        $offer->update(['enabled' => false, 'reward_value' => 50]);
        $service->calculateOrderTotals($order);
        $this->assertEquals(900, $order->fresh()->total);
        $this->assertFalse($order->fresh()->offer_snapshot['provisional']);
    }

    public function test_changed_quote_and_foreign_cart_payload_are_rejected(): void
    {
        [$order] = $this->checkoutFixture();
        $this->offer(['reward_type' => 'free_delivery', 'reward_value' => null]);
        $service = $this->app->make(\App\Services\Order\OrderService::class);
        $method = new \ReflectionMethod($service, 'applyStorefrontPricing');
        foreach ([['quote_total' => 1, 'items' => [['product_id' => 1, 'quantity' => 2]]],
            ['quote_total' => 1000, 'items' => [['product_id' => 999, 'quantity' => 2]]]] as $payload) {
            try {
                DB::transaction(fn () => $method->invoke($service, $order, $payload));
                $this->fail('Unverified pricing was accepted.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $this->assertNull($order->fresh()->offer_snapshot);
    }

    public function test_coupon_quote_matches_saved_line_discount_and_shipping(): void
    {
        [$order] = $this->checkoutFixture();
        $order->update(['shipping_district' => 'Dhaka']);
        $this->offer();
        $pricing = new class extends CheckoutPricingService
        {
            public function deliveryCharge(string $district, ?string $thana): float
            {
                return 60;
            }
        };
        $this->app->instance(CheckoutPricingService::class, $pricing);
        $coupon = new \App\Models\Coupon(['code' => 'SAVE', 'discount_type' => 'percentage', 'discount_value' => 15]);
        $coupons = \Mockery::mock(\App\Services\Coupon\CouponService::class);
        $coupons->shouldReceive('verifyCoupon')->with('SAVE')->andReturn($coupon);
        $coupons->shouldReceive('applyToCart')->with('SAVE', 1)->andReturn([
            'summary' => ['total_discount' => 150],
            'items' => [['cart_item_id' => 1, 'is_eligible' => true, 'coupon_code' => 'SAVE', 'discount_type' => 'percentage', 'discount_amount' => 15, 'discount' => 150]],
        ]);
        $this->app->instance(\App\Services\Coupon\CouponService::class, $coupons);
        session(['applied_coupon' => 'SAVE']);
        $service = $this->app->make(\App\Services\Order\OrderService::class);
        (new \ReflectionMethod($service, 'applyStorefrontPricing'))->invoke($service, $order, ['quote_total' => 910, 'items' => [['product_id' => 1, 'quantity' => 2]]]);
        $service->calculateOrderTotals($order);
        $this->assertEquals(910, $order->fresh()->total);
        $this->assertEquals(0, $order->fresh()->global_discount_total);
        $this->assertEquals(150, $order->fresh()->discount_total);
        $this->assertSame('SAVE', $order->items->first()->coupon_code);
        $this->assertEquals(60, $order->fresh()->shipping_cost);
    }

    public function test_free_delivery_saving_is_persisted_without_subtracting_it_twice(): void
    {
        [$order] = $this->checkoutFixture();
        $order->update(['shipping_district' => 'Outside Dhaka']);
        $this->offer();
        $this->offer(['reward_type' => 'free_delivery', 'reward_value' => null]);
        $pricing = new class extends CheckoutPricingService
        {
            public function deliveryCharge(string $district, ?string $thana): float
            {
                return 120;
            }
        };
        $this->app->instance(CheckoutPricingService::class, $pricing);
        $service = $this->app->make(\App\Services\Order\OrderService::class);
        (new \ReflectionMethod($service, 'applyStorefrontPricing'))->invoke($service, $order, ['quote_total' => 900, 'items' => [['product_id' => 1, 'quantity' => 2]]]);
        $service->calculateOrderTotals($order);
        $saved = $order->fresh();
        $this->assertEquals(900, $saved->total);
        $this->assertEquals(120, $saved->normal_shipping_charge);
        $this->assertEquals(120, $saved->shipping_discount_total);
        $this->assertEquals(100, $saved->discount_total);
    }

    public function test_incomplete_order_rechecks_expired_rewards_before_confirmation(): void
    {
        [$order] = $this->checkoutFixture();
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->boolean('is_free_delivery')->default(false);
            $table->softDeletes();
        });
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
        });
        Schema::create('campaign_products', function (Blueprint $table) {
            $table->integer('product_id');
            $table->integer('campaign_id');
            $table->timestamps();
        });
        DB::table('products')->insert(['id' => 1, 'name' => 'Example', 'price' => 500]);
        $order->update(['status' => 'incomplete']);
        $offer = $this->offer();
        $service = $this->app->make(\App\Services\Order\OrderService::class);
        (new \ReflectionMethod($service, 'applyStorefrontPricing'))->invoke($service, $order, ['items' => [['product_id' => 1, 'quantity' => 2]]]);
        $this->assertTrue($order->fresh()->offer_snapshot['provisional']);
        $offer->update(['ends_at' => now()->subSecond()]);
        (new \ReflectionMethod($service, 'confirmProvisionalOffers'))->invoke($service, $order);
        $saved = $order->fresh();
        $this->assertFalse($saved->offer_snapshot['provisional']);
        $this->assertEquals(0, $saved->global_discount_total);
        $this->assertEquals(1000, $saved->total);
    }

    public function test_admin_validates_repeatable_tiers_and_allows_free_delivery(): void
    {
        $controller = new \App\Http\Controllers\Admin\GlobalOfferController;
        $method = new \ReflectionMethod($controller, 'validated');
        $tier = ['minimum_subtotal' => 1000, 'reward_type' => 'percentage', 'reward_value' => 10];
        foreach ([['minimum_subtotal' => 0], ['reward_value' => 101], ['reward_type' => 'invalid']] as $invalid) {
            try {
                $method->invoke($controller, \Illuminate\Http\Request::create('/admin/global-offers', 'POST', ['enabled' => true, 'tiers' => [array_merge($tier, $invalid)]]));
                $this->fail('Invalid tier was accepted.');
            } catch (\Illuminate\Validation\ValidationException $exception) {
                $this->assertNotEmpty($exception->errors());
            }
        }
        $valid = $method->invoke($controller, \Illuminate\Http\Request::create('/admin/global-offers', 'POST', ['enabled' => true, 'tiers' => [
            $tier, ['minimum_subtotal' => 2000, 'reward_type' => 'percentage', 'reward_value' => 20],
            ['minimum_subtotal' => 3000, 'reward_type' => 'free_delivery', 'reward_value' => null],
        ]]));
        $this->assertCount(3, $valid['tiers']);
        $this->assertNull($valid['tiers'][2]['reward_value']);
    }

    public function test_one_save_updates_all_tiers_and_repeated_saves_preserve_ids(): void
    {
        $existing = $this->offer(['enabled' => false]);
        $controller = new \App\Http\Controllers\Admin\GlobalOfferController;
        $payload = ['enabled' => true, 'tiers' => [
            ['id' => $existing->id, 'minimum_subtotal' => 1000, 'reward_type' => 'percentage', 'reward_value' => 10],
            ['id' => null, 'minimum_subtotal' => 2000, 'reward_type' => 'percentage', 'reward_value' => 20],
        ]];
        $controller->store(\Illuminate\Http\Request::create('/admin/global-offers', 'POST', $payload));
        $this->assertCount(2, GlobalOffer::all());
        $this->assertTrue($existing->fresh()->enabled);
        $this->assertEquals(10, (new GlobalOfferService)->evaluate(1000)['milestones'][0]['reward_value']);
        $this->assertEquals(400, (new GlobalOfferService)->evaluate(2000)['global_discount']);
        $payload['tiers'] = GlobalOffer::orderBy('minimum_subtotal')->get()->map(fn ($offer) => $offer->only(['id', 'minimum_subtotal', 'reward_type', 'reward_value']))->all();
        $controller->store(\Illuminate\Http\Request::create('/admin/global-offers', 'POST', $payload));
        $this->assertCount(2, GlobalOffer::withTrashed()->get());
        $payload['enabled'] = false;
        $controller->store(\Illuminate\Http\Request::create('/admin/global-offers', 'POST', $payload));
        $this->assertEmpty((new GlobalOfferService)->evaluate(1000)['milestones']);
    }
}
