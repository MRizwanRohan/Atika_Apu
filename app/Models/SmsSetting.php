<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    protected $fillable = [
        'api_key',
        'sender_id',
        'is_enabled',
        'order_create_sms_enabled',
        'order_create_sms_template',
        'order_cancel_sms_enabled',
        'order_cancel_sms_template',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'order_create_sms_enabled' => 'boolean',
        'order_cancel_sms_enabled' => 'boolean',
    ];
}
