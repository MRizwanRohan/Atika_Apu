<?php

namespace Tests\Unit;

use App\Contracts\CourierServiceInterface;
use App\Http\Controllers\Admin\Courier\ShipmentTrackingController;
use App\Models\Order;
use App\Services\Courier\CourierManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ShipmentTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('courier_name');
            $table->string('consignment_id')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('delivery_status')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function test_tracking_uses_consignment_id_and_saves_delivery_status(): void
    {
        $order = Order::create(['courier_name' => 'steadfast', 'consignment_id' => '297543021', 'tracking_number' => 'SFR-TRACKING-CODE']);
        $driver = Mockery::mock(CourierServiceInterface::class);
        $driver->shouldReceive('getStatusByConsignmentId')->once()->with('297543021')->andReturn(['status' => 200, 'delivery_status' => 'delivered']);
        $manager = Mockery::mock(CourierManager::class);
        $manager->shouldReceive('driver')->with('steadfast')->andReturn($driver);
        $response = (new ShipmentTrackingController)->show($order, $manager);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('delivered', $response->getData(true)['delivery_status']);
        $this->assertSame('delivered', $order->fresh()->delivery_status);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_missing_consignment_is_rejected_without_using_tracking_code(): void
    {
        $order = Order::create(['courier_name' => 'steadfast', 'tracking_number' => 'SFR-TRACKING-CODE']);
        $manager = Mockery::mock(CourierManager::class);
        $manager->shouldNotReceive('driver');
        $this->assertEquals(422, (new ShipmentTrackingController)->show($order, $manager)->getStatusCode());
    }

    public function test_failed_status_does_not_overwrite_saved_delivery_status(): void
    {
        $order = Order::create(['courier_name' => 'steadfast', 'consignment_id' => '123', 'delivery_status' => 'pending']);
        $driver = Mockery::mock(CourierServiceInterface::class);
        $driver->shouldReceive('getStatusByConsignmentId')->andReturn(['status' => 404, 'delivery_status' => null]);
        $manager = Mockery::mock(CourierManager::class);
        $manager->shouldReceive('driver')->andReturn($driver);
        $response = (new ShipmentTrackingController)->show($order, $manager);
        $this->assertEquals(502, $response->getStatusCode());
        $this->assertSame('pending', $order->fresh()->delivery_status);
    }
}
