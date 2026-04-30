<?php

namespace App\Services\Sms;

use App\Models\SmsSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $apiKey;
    protected $senderId;
    protected $isEnabled;

    public function __construct()
    {
        $settings = SmsSetting::first();
        if ($settings) {
            $this->apiKey = $settings->api_key;
            $this->senderId = $settings->sender_id;
            $this->isEnabled = $settings->is_enabled;
        }
    }

    /**
     * Send individual SMS
     * 
     * @param string $number
     * @param string $message
     * @return array
     */
    public function sendSms($number, $message)
    {
        if (!$this->isEnabled || !$this->apiKey) {
            return ['status' => false, 'message' => 'SMS service is disabled or API key missing.'];
        }

        try {
            $response = Http::timeout(5)->get('http://bulksmsbd.net/api/smsapi', [
                'api_key' => $this->apiKey,
                'type' => 'text',
                'number' => $this->formatNumber($number),
                'senderid' => $this->senderId,
                'message' => $message,
            ]);


            if ($response->successful()) {
                $data = $response->json();
                // 202 is success according to document
                if (isset($data['response_code']) && $data['response_code'] == 202) {
                    return ['status' => true, 'message' => 'SMS sent successfully.'];
                }
                return ['status' => false, 'message' => $data['success_message'] ?? 'SMS sending failed (Code: ' . ($data['response_code'] ?? 'Unknown') . ')'];
            }

            return ['status' => false, 'message' => 'Could not connect to SMS Gateway. Status: ' . $response->status()];
        } catch (\Exception $e) {
            Log::error('SMS API Error: ' . $e->getMessage());
            return ['status' => false, 'message' => 'An error occurred while sending SMS.'];
        }
    }

    /**
     * Get API Balance
     * 
     * @return float|string
     */
    public function getBalance()
    {
        if (!$this->apiKey) return '0.00';

        try {
            $response = Http::timeout(5)->get('http://bulksmsbd.net/api/getBalanceApi', [
                'api_key' => $this->apiKey,
            ]);


            if ($response->successful()) {
                $data = $response->json();
                return $data['balance'] ?? '0.00';
            }
        } catch (\Exception $e) {
            Log::error('SMS Balance API Error: ' . $e->getMessage());
        }

        return '0.00';
    }

    /**
     * Format number for Bangladeshi carriers
     * 
     * @param string $number
     * @return string
     */
    protected function formatNumber($number)
    {
        // Remove non-numeric characters
        $number = preg_replace('/[^0-9]/', '', $number);
        
        // Add 88 if not present (Bangladeshi numbers)
        if (strlen($number) == 11 && strpos($number, '01') === 0) {
            $number = '88' . $number;
        }
        
        return $number;
    }
}
