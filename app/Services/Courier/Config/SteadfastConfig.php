<?php

namespace App\Services\Courier\Config;

use App\Contracts\CourierConfigInterface;
use App\Models\CourierSetting;

class SteadfastConfig implements CourierConfigInterface
{
    private $settings;

    private function getSettings()
    {
        if ($this->settings === null) {
            try {
                $this->settings = CourierSetting::first() ?? new CourierSetting();
            } catch (\Exception $e) {
                $this->settings = new CourierSetting();
            }
        }

        return $this->settings;
    }

    public function __construct()
    {
        // Settings are lazily loaded
    }

    public function getApiKey(): string
    {
        return $this->getSettings()->steadfast_api_key ?? '';
    }

    public function getSecretKey(): string
    {
        return $this->getSettings()->steadfast_secret_key ?? '';
    }

    public function getBaseUrl(): string
    {
        $url = config('courier.steadfast.base_url');
        if (empty($url)) {
            $url = 'https://portal.packzy.com/api/v1';
        }
        return rtrim($url, '/');
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
