<?php

namespace App\Services\Courier\Config;

use App\Contracts\CourierConfigInterface;
use App\Models\CourierSetting;

class SteadfastConfig implements CourierConfigInterface
{
    private $settings;

    public function __construct()
    {
        $this->settings = CourierSetting::first();
    }

    public function getApiKey(): string
    {
        return $this->settings->steadfast_api_key ?? '';
    }

    public function getSecretKey(): string
    {
        return $this->settings->steadfast_secret_key ?? '';
    }

    public function getBaseUrl(): string
    {
        return config('courier.steadfast.base_url', 'https://portal.packzy.com/api/v1');
    }

    public function getHeaders(): array
    {
        return [
            'Api-Key' => $this->getApiKey(),
            'Secret-Key' => $this->getSecretKey(),
            'Content-Type' => 'application/json',
        ];
    }
}
