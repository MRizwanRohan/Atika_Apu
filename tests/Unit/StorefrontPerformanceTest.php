<?php

namespace Tests\Unit;

use App\Contracts\ProductGroupRepositoryInterface;
use App\Http\Controllers\Frontend\Home\HomeController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Resources\Cart\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Services\Cart\CartService;
use App\Services\Categories\CategoryService;
use App\Services\Compare\CompareService;
use App\Services\Order\OrderInterface;
use App\Services\Product\ProductService;
use App\Services\ProductGroup\ProductGroupService;
use App\Services\Settings\SettingsService;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Repository\ProductGroup\ProductGroupRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class StorefrontPerformanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
    }

    private function request(string $session = 'test-cart'): Request
    {
        $request = Request::create('/');
        $request->cookies->set('cart_session_id', $session);
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => null);
        return $request;
    }

    public function test_cart_and_compare_load_once_per_response_and_are_isolated_between_requests(): void
    {
        $cart = new Cart;
        $cart->setRelation('items', collect([new CartItem(['price' => 100, 'quantity' => 2])]));
        $cartService = Mockery::mock(CartService::class);
        $cartService->shouldReceive('getCart')->once()->with(null, 'first')->andReturn($cart);
        $cartService->shouldReceive('getCart')->once()->with(null, 'second')->andReturn(null);
        $compare = Mockery::mock(CompareService::class);
        $compare->shouldReceive('getCompareIds')->once()->with(null, 'first')->andReturn([3, 4]);
        $middleware = new HandleInertiaRequests(
            Mockery::mock(CategoryService::class), $cartService, Mockery::mock(SettingsService::class),
            Mockery::mock(WishlistService::class), $compare, Mockery::mock(OrderInterface::class)
        );
        $props = $middleware->share($this->request('first'));
        $this->assertInstanceOf(CartResource::class, $props['cart']());
        $this->assertSame(1, $props['cartCount']());
        $this->assertEquals(200, $props['cartTotal']());
        $this->assertSame(2, $props['compareCount']());
        $this->assertSame([3, 4], $props['compareIds']());
        $next = $middleware->share($this->request('second'));
        $this->assertNull($next['cart']());
        $this->assertSame(0, $next['cartCount']());
        $this->assertSame(0, $next['cartTotal']());
    }

    public function test_initial_home_response_omits_below_fold_queries_and_partial_reload_fetches_only_requested_data(): void
    {
        Cache::put('sliders.all', collect(), 3600);
        $products = Mockery::mock(ProductService::class);
        $products->shouldReceive('categorywithproducts')->once()->andReturn([['name' => 'Sample', 'products' => []]]);
        $groups = Mockery::mock(ProductGroupService::class);
        $controller = new HomeController($products, $groups);
        $request = $this->request();
        $request->headers->set('X-Inertia', 'true');
        $initial = $controller->index()->toResponse($request)->getData(true)['props'];
        $this->assertArrayHasKey('sliders', $initial);
        foreach (['activeCampaigns', 'groupedProducts', 'productGroups', 'productswithVideo', 'instagramFeeds'] as $key) {
            $this->assertArrayNotHasKey($key, $initial);
        }
        $request->headers->set('X-Inertia-Partial-Component', 'Frontend/Home/Home');
        $request->headers->set('X-Inertia-Partial-Data', 'groupedProducts');
        $partial = $controller->index()->toResponse($request)->getData(true)['props'];
        $this->assertSame(['groupedProducts'], array_keys($partial));
        $this->assertSame('Sample', $partial['groupedProducts'][0]['name']);
    }

    public function test_group_pages_are_cached_and_invalidated_by_group_and_product_changes(): void
    {
        $repository = Mockery::mock(ProductGroupRepositoryInterface::class);
        $repository->shouldReceive('homepagePage')->with(1)->times(3)->andReturnUsing(
            fn () => new LengthAwarePaginator(collect([new ProductGroup(['name' => 'Collection'])]), 8, 6, 1)
        );
        $group = new ProductGroup;
        $repository->shouldReceive('toggleStatus')->once()->with($group)->andReturn(true);
        $service = new ProductGroupService($repository);
        $first = $service->homepagePage();
        $this->assertSame(6, $first->perPage());
        $this->assertStringContainsString('/home/product-groups?page=2', $first->nextPageUrl());
        $service->homepagePage(); // Cache hit.
        $service->toggleStatus($group);
        $service->homepagePage();
        Product::incrementCacheVersion();
        $service->homepagePage();
    }
    public function test_repository_returns_six_groups_and_ten_products_per_group(): void
    {
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug'); $table->string('banner')->nullable();
            $table->boolean('status'); $table->integer('order_number'); $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'slug', 'product_code', 'type', 'feature_image', 'gallery_images'] as $field) $table->string($field)->nullable();
            foreach (['price', 'previous_price', 'stock', 'brand_id'] as $field) $table->integer($field)->nullable();
            $table->boolean('is_pre_order')->default(false); $table->boolean('is_free_delivery')->default(false);
            $table->string('status'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('reviews', function (Blueprint $table) {
            $table->id(); $table->integer('product_id'); $table->integer('rating'); $table->boolean('is_approved');
        });
        Schema::create('product_group_product', function (Blueprint $table) {
            $table->integer('product_group_id'); $table->integer('product_id'); $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); $table->integer('parent_id')->nullable(); $table->softDeletes();
        });
        Schema::create('category_product', function (Blueprint $table) {
            $table->integer('category_id'); $table->integer('product_id'); $table->timestamps();
        });
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id(); $table->integer('product_id'); $table->softDeletes();
        });
        Schema::create('campaigns', fn (Blueprint $table) => $table->id());
        Schema::create('campaign_products', function (Blueprint $table) {
            $table->integer('campaign_id'); $table->integer('product_id'); $table->timestamps();
        });
        for ($id = 1; $id <= 8; $id++) {
            $group = ProductGroup::create(['name' => "Group {$id}", 'status' => true, 'order_number' => $id]);
            for ($number = 1; $number <= 12; $number++) {
                $product = Product::create(['name' => "Product {$id}-{$number}", 'status' => 'Published', 'type' => 'simple', 'price' => 100]);
                $group->products()->attach($product->id);
            }
        }
        $repository = new ProductGroupRepository;
        $first = $repository->homepagePage(1);
        $this->assertCount(6, $first->items());
        foreach ($first->items() as $group) $this->assertCount(10, $group->products);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $payload = $first->toArray();
        $this->assertSame([], DB::getQueryLog(), 'Card serialization must not issue hidden category/review queries.');
        DB::disableQueryLog();
        $this->assertArrayNotHasKey('category', $payload['data'][0]['products'][0]);
        $this->assertArrayNotHasKey('description', $payload['data'][0]['products'][0]);
        $second = $repository->homepagePage(2);
        $this->assertSame([7, 8], $second->getCollection()->pluck('id')->all());
        foreach ($second->items() as $group) $this->assertCount(10, $group->products);
    }

}
