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
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_enabled' => 'string',
        'steadfast_is_enabled' => 'string',
    ];
}
