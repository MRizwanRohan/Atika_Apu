<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GlobalOffer extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'enabled', 'minimum_subtotal', 'reward_type', 'reward_value', 'maximum_discount', 'priority', 'starts_at', 'ends_at'];

    protected $casts = ['enabled' => 'boolean', 'minimum_subtotal' => 'decimal:2', 'reward_value' => 'decimal:2', 'maximum_discount' => 'decimal:2', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
}
