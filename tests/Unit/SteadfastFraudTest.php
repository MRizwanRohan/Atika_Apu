<?php

namespace Tests\Unit;

use App\Services\Couriers\Steadfast\SteadfastService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SteadfastFraudTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('courier_settings', function (Blueprint $table) {
            $table->id();
            $table->string('steadfast_api_key');
            $table->string('steadfast_secret_key');
        });
        Schema::create('courier_fraud_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone');
            $table->string('courier_name');
            $table->json('response_data');
            $table->timestamps();
        });
        DB::table('courier_settings')->insert(['steadfast_api_key' => 'test-key', 'steadfast_secret_key' => 'test-secret']);
        Http::preventStrayRequests();
    }

    public function test_score_endpoint_maps_ratios_without_inventing_counts_and_reuses_new_cache(): void
    {
        Http::fake(['*/fraud_check/score/01712345678' => Http::response([
            'status' => 200, 'phone' => '01712345678', 'delivery_ratio' => 92, 'cancellation_ratio' => 7,
            'volume_band' => 'high', 'volume_range' => '50+', 'total_reports' => 0,
            'fraud_categories' => [], 'score' => null, 'level' => null, 'reasons' => [],
            'scoring_disabled' => true, 'doubtful_reports' => false,
        ])]);
        $service = new SteadfastService;
        $result = $service->checkFraud('+8801712345678');
        $this->assertEquals(92, $result['delivery_ratio']);
        $this->assertEquals(7, $result['cancellation_ratio']);
        $this->assertSame('50+', $result['volume_range']);
        $this->assertNull($result['success']);
        $this->assertNull($result['total']);
        $this->assertNull($result['score']);
        $this->assertTrue($result['scoring_disabled']);
        $this->assertEquals($result, $service->checkFraud('01712345678'));
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/fraud_check/score/01712345678') && $request->hasHeader('Api-Key', 'test-key'));
    }

    public function test_old_cache_is_bypassed_and_failures_are_not_cached(): void
    {
        \App\Models\CourierFraudLog::create(['phone' => '01712345678', 'courier_name' => 'steadfast', 'response_data' => ['success' => 0, 'total' => 0]]);
        Http::fake(['*' => Http::response(['status' => 401], 401)]);
        $result = (new SteadfastService)->checkFraud('01712345678');
        $this->assertArrayHasKey('error', $result);
        Http::assertSentCount(1);
        $this->assertEquals(1, \App\Models\CourierFraudLog::count());
    }

    public function test_invalid_phone_does_not_call_api(): void
    {
        $this->assertArrayHasKey('error', (new SteadfastService)->checkFraud('../invalid'));
        Http::assertNothingSent();
    }

    public function test_aggregate_uses_delivery_ratio_and_keeps_counts_unknown(): void
    {
        $controller = (new \ReflectionClass(\App\Http\Controllers\Admin\Courier\FraudcheckController::class))->newInstanceWithoutConstructor();
        $result = (new \ReflectionMethod($controller, 'calculateAggregatedMetrics'))->invoke($controller, [], [
            'delivery_ratio' => 92, 'cancellation_ratio' => 7, 'volume_range' => '50+',
        ]);
        $this->assertEquals(92, $result['success_rate']);
        $this->assertEquals(7, $result['cancellation_ratio']);
        $this->assertSame('50+', $result['volume_range']);
        $this->assertNull($result['total_orders']);
        $this->assertNull($result['total_success']);
    }
}
