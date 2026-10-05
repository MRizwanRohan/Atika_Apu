<?php

namespace App\Services\Couriers\Steadfast;

use App\Models\CourierFraudLog;
use App\Models\CourierSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
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
        $phoneNumber = preg_replace('/^(?:\+?88)/', '', trim((string) $phoneNumber));
        if (! preg_match('/^01[3-9]\d{8}$/', $phoneNumber)) {
            return ['error' => 'Please provide a valid Bangladesh phone number.'];
        }

        // 1. Check local database first (within last 24 hours)
        $cachedLog = CourierFraudLog::where('phone', $phoneNumber)
            ->where('courier_name', 'steadfast')
            ->where('created_at', '>=', Carbon::now()->subDay())
            ->where('response_data->schema_version', 2)
            ->latest('id')->first();

        if ($cachedLog) {
            return $cachedLog->response_data;
        }

        if (! $this->apiKey || ! $this->secretKey) {
            return ['error' => 'Steadfast API credentials not configured in database'];
        }

        try {
            $response = Http::withHeaders([
                'Api-Key' => $this->apiKey,
                'Secret-Key' => $this->secretKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/fraud_check/score/{$phoneNumber}");

            if (! $response->successful()) {
                return ['error' => 'Failed to fetch fraud data from Steadfast API'];
            }

            $data = $response->json();

            if (! is_array($data) || (isset($data['status']) && (int) $data['status'] !== 200)
                || ! array_key_exists('delivery_ratio', $data) || ! array_key_exists('cancellation_ratio', $data)) {
                return ['error' => 'Steadfast returned an invalid fraud-check response.'];
            }
            $result = [
                'schema_version' => 2,
                'status' => $data['status'] ?? 200,
                'phone' => $data['phone'] ?? $phoneNumber,
                'delivery_ratio' => is_numeric($data['delivery_ratio']) ? min(100, max(0, (float) $data['delivery_ratio'])) : null,
                'cancellation_ratio' => is_numeric($data['cancellation_ratio']) ? min(100, max(0, (float) $data['cancellation_ratio'])) : null,
                'volume_band' => $data['volume_band'] ?? null,
                'volume_range' => $data['volume_range'] ?? null,
                'total_reports' => $data['total_reports'] ?? 0,
                'fraud_categories' => $data['fraud_categories'] ?? [],
                'score' => $data['score'] ?? null,
                'level' => $data['level'] ?? null,
                'reasons' => $data['reasons'] ?? [],
                'scoring_disabled' => (bool) ($data['scoring_disabled'] ?? true),
                'doubtful_reports' => (bool) ($data['doubtful_reports'] ?? false),
                // The score API provides ratios and a volume range, not exact parcel counts.
                'success' => null, 'cancel' => null, 'total' => null,
            ];

            // 2. Save result to database for caching
            CourierFraudLog::create([
                'phone' => $phoneNumber,
                'courier_name' => 'steadfast',
                'response_data' => $result,
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::warning('Steadfast fraud check failed', ['exception' => get_class($e)]);

            return ['error' => 'Unable to fetch Steadfast delivery records. Please try again.'];
        }
    }
}
