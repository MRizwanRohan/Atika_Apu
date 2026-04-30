<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'district',
        'thana',
        'area',
        'orders_count',
        'total_spent',
        'last_order_at',
    ];

    protected $casts = [
        'last_order_at' => 'datetime',
        'total_spent' => 'decimal:2',
        'orders_count' => 'integer',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_phone', 'phone');
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }
}

