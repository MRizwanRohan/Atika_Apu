<?php

namespace App\Services\Couriers\Steadfast;

use Illuminate\Support\Facades\Http;
use App\Models\CourierSetting;
use App\Models\CourierFraudLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SteadfastService
{
    private $apiKey;
    private $secretKey;
    private $baseUrl;

    public function __construct()
    {
        $settings = CourierSetting::first();
        $this->apiKey = $settings->steadfast_api_key ?? null;
        $this->secretKey = $settings->steadfast_secret_key ?? null;
        $this->baseUrl = 'https://portal.packzy.com/api/v1';
    }

    public function checkFraud($phoneNumber)
    {
        // 1. Check local database first (within last 24 hours)
        $cachedLog = CourierFraudLog::where('phone', $phoneNumber)
            ->where('courier_name', 'steadfast')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->first();

        if ($cachedLog) {
            return $cachedLog->response_data;
        }

        if (!$this->apiKey || !$this->secretKey) {
            return ['error' => 'Steadfast API credentials not configured in database'];
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/fraud_check/{$phoneNumber}");

            if (!$response->successful()) {
                return ['error' => 'Failed to fetch fraud data from Steadfast API'];
            }

            $data = $response->json();

            $result = [
                'success' => $data['total_delivered'] ?? 0,
                'cancel' => $data['total_cancelled'] ?? 0,
                'total' => $data['total_parcels'] ?? 0,
                'fraud_reports' => $data['total_fraud_reports'] ?? [],
            ];

            // 2. Save result to database for caching
            CourierFraudLog::create([
                'phone' => $phoneNumber,
                'courier_name' => 'steadfast',
                'response_data' => $result,
            ]);

            return $result;
        } catch (\Exception $e) {
            return ['error' => 'Steadfast API Error: ' . $e->getMessage()];
        }
    }
}