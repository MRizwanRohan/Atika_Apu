<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TrackingSettings extends Model
{
    protected $fillable = [
        'pixel_id',
        'access_token',
        'test_event_code',
        'api_version',
        'pixel_enabled',
        'capi_enabled',
        'test_mode',
        'purchase_fire_mode',
    ];

    protected $casts = [
        'pixel_enabled' => 'boolean',
        'capi_enabled'  => 'boolean',
        'test_mode'     => 'boolean',
    ];

    protected $hidden = ['access_token']; // Never serialize raw token

    const CACHE_KEY = 'tracking_settings';
    const CACHE_TTL = 3600; // 1 hour

    /**
     * Get cached settings (singleton-style, always returns first row).
     */
    public static function getSettings(): self
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::firstOrCreate([], [
                'pixel_enabled'       => false,
                'capi_enabled'        => false,
                'test_mode'           => false,
                'purchase_fire_mode'  => 'on_order_created',
                'api_version'         => 'v22.0',
            ]);
        });
    }

    /**
     * Clear settings cache (call after update).
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Get the raw access token (bypasses $hidden for internal use).
     */
    public function getRawAccessToken(): ?string
    {
        return $this->getRawOriginal('access_token');
    }
}
