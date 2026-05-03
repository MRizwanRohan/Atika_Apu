<?php

namespace App\Services\Webhook;

use Illuminate\Support\Str;
use App\Models\CourierSetting;

class WebhookTokenService
{
    /**
     * Generate a new webhook token for a courier
     */
    public function generateToken(string $courier): string
    {
        $token = Str::uuid()->toString();
        $settings = CourierSetting::first();

        if (!$settings) {
            $settings = CourierSetting::create([]);
        }

        $field = $courier === 'pathao' ? 'pathao_webhook_token' : 'steadfast_webhook_token';
        $settings->update([$field => $token]);

        return $token;
    }

    /**
     * Ensure a token exists for a courier, generate if missing
     */
    public function ensureToken(string $courier): string
    {
        $settings = CourierSetting::first();
        $field = $courier === 'pathao' ? 'pathao_webhook_token' : 'steadfast_webhook_token';

        if ($settings && $settings->{$field}) {
            return $settings->{$field};
        }

        return $this->generateToken($courier);
    }
}
