<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookEntry extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'customer_address',
        'bill_amount',
        'shipping_cost',
        'area',
        'items',
        'tracking_id',
        'status',
    ];

    protected $casts = [
        'items' => 'json',
    ];
}
