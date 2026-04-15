<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierFraudLog extends Model
{
    protected $fillable = [
        'phone',
        'courier_name',
        'response_data',
    ];

    protected $casts = [
        'response_data' => 'array',
    ];
}
