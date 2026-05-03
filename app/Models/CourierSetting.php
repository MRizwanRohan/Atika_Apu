<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierSetting extends Model
{
    protected $fillable = [
        'client_id',
        'client_secret',
        'username',
        'password',
        'access_token',
        'expires_at',
        'is_enabled',
        'StoreId',
        'steadfast_api_key',
        'steadfast_secret_key',
        'steadfast_is_enabled',
        // Pathao Webhook
        'pathao_webhook_enabled',
        'pathao_webhook_token',
        'pathao_status_mapping',
        // Steadfast Webhook
        'steadfast_webhook_enabled',
        'steadfast_webhook_token',
        'steadfast_status_mapping',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_enabled' => 'string',
        'steadfast_is_enabled' => 'string',
        'pathao_status_mapping' => 'array',
        'steadfast_status_mapping' => 'array',
    ];
}
