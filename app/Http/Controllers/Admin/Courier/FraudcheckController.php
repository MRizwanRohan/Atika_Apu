<?php

namespace App\Http\Controllers\Admin\Courier;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Couriers\Pathao\PathaoSuccessRateService;
use App\Services\Couriers\Steadfast\SteadfastService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FraudcheckController extends Controller
{
    protected $PathaoSuccessRateService;

    protected $steadfastService;

    public function __construct(PathaoSuccessRateService $pathaoSuccessRateService, SteadfastService $steadfastService)
    {
        $this->PathaoSuccessRateService = $pathaoSuccessRateService;
        $this->steadfastService = $steadfastService;
    }

    public function fetchSuccessRate(Request $request)
    {
        $request->validate(['phone' => ['required', 'string', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/']]);
        $phone = preg_replace('/^(?:\+?88)/', '', $request->input('phone'));

        // Fetch Steadfast data first (Fast if cached)
        $steadfastResponse = $this->fetchSteadfastData($phone);
        $steadfastData = $steadfastResponse->getData(true);

        // Fetch Pathao data (Temporarily disabled)
        // $pathaoResponse = $this->fetchPathaoData($phone);
        // $pathaoData = $pathaoResponse->getData(true);
        $pathaoData = [];

        // Calculate aggregated metrics
        $aggregated = $this->calculateAggregatedMetrics($pathaoData, $steadfastData);

        // Prepare response data
        $data = [
            'phone' => $phone,
            'pathao' => $pathaoData,
            'steadfast' => $steadfastData,
            'aggregated' => $aggregated,
        ];

        // Save to database for caching (use courier_name 'all' for aggregated results)
        \App\Models\CourierFraudLog::create([
            'phone' => $phone,
            'courier_name' => 'all',
            'response_data' => $data,
        ]);

        Order::incrementCacheVersion();

        return response()->json($data);
    }

    public function bulkFetchSuccessRate(Request $request)
    {
        $request->validate(['phones' => 'required|array|min:1|max:100', 'phones.*' => ['required', 'string', 'regex:/^(?:\+?88)?01[3-9]\d{8}$/']]);
        $phones = array_unique(array_map(fn ($phone) => preg_replace('/^(?:\+?88)/', '', $phone), $request->input('phones')));

        if (empty($phones)) {
            return response()->json(['error' => 'No phone numbers provided'], 400);
        }

        $results = [];
        foreach ($phones as $phone) {
            // Fetch Steadfast data
            $steadfastResponse = $this->fetchSteadfastData($phone);
            $steadfastData = $steadfastResponse->getData(true);

            // Pathao fallback/aggregated
            $pathaoData = [];
            $aggregated = $this->calculateAggregatedMetrics($pathaoData, $steadfastData);

            $data = [
                'phone' => $phone,
                'steadfast' => $steadfastData,
                'pathao' => $pathaoData,
                'aggregated' => $aggregated,
            ];

            // Save to database
            \App\Models\CourierFraudLog::create([
                'phone' => $phone,
                'courier_name' => 'all',
                'response_data' => $data,
            ]);

            $results[$phone] = $data;
        }

        Order::incrementCacheVersion();

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    private function fetchPathaoData($phone)
    {
        $pathao = $this->PathaoSuccessRateService->successRate($phone);
        Log::info('Pathao success rate response: '.json_encode($pathao));

        return response()->json([
            'success' => true,
            'message' => 'Success rate fetched successfully',
            'success_rate' => $pathao['data']['success_rate'] ?? null,
            'customer_number' => $pathao['data']['customer']['customer_number'] ?? null,
            'successful_delivery' => $pathao['data']['customer']['successful_delivery'] ?? 0,
            'total_delivery' => $pathao['data']['customer']['total_delivery'] ?? 0,
            'fraud_level' => $pathao['data']['customer']['fraud_level'] ?? 0,
            'fraud_count' => $pathao['data']['customer']['fraud_count'] ?? 0,
        ]);
    }

    private function fetchSteadfastData($phoneNumber)
    {
        $result = $this->steadfastService->checkFraud($phoneNumber);

        return response()->json($result);
    }

    private function calculateAggregatedMetrics(array $pathaoData, array $steadfastData)
    {
        if (array_key_exists('delivery_ratio', $steadfastData) || isset($steadfastData['error'])) {
            return [
                'total_success' => null, 'total_cancel' => null, 'total_orders' => null,
                'success_rate' => $steadfastData['delivery_ratio'] ?? null,
                'cancellation_ratio' => $steadfastData['cancellation_ratio'] ?? null,
                'volume_range' => $steadfastData['volume_range'] ?? null,
                'volume_band' => $steadfastData['volume_band'] ?? null,
            ];
        }

        // Extract relevant metrics
        $pathaoSuccess = $pathaoData['successful_delivery'] ?? 0;
        $pathaoTotal = $pathaoData['total_delivery'] ?? 0;
        $pathaoCancel = $pathaoTotal - $pathaoSuccess;

        $steadfastSuccess = $steadfastData['success'] ?? 0;
        $steadfastCancel = $steadfastData['cancel'] ?? 0;
        $steadfastTotal = $steadfastData['total'] ?? 0;

        // Calculate aggregated metrics
        $totalSuccess = $pathaoSuccess + $steadfastSuccess;
        $totalCancel = $pathaoCancel + $steadfastCancel;
        $totalOrders = $pathaoTotal + $steadfastTotal;

        // Calculate success rate
        $successRate = $totalOrders > 0 ? ($totalSuccess / $totalOrders) * 100 : 0;
        $successRate = round($successRate, 2);

        return [
            'total_success' => $totalSuccess,
            'total_cancel' => $totalCancel,
            'total_orders' => $totalOrders,
            'success_rate' => $successRate,
        ];
    }
}
