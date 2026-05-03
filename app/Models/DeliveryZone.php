<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryZone extends Model
{
    protected $fillable = [
        'type',
        'name',
        'bn_name',
        'json_id',
        'parent_id',
        'charge',
        'is_active',
    ];

    public function children()
    {
        return $this->hasMany(DeliveryZone::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(DeliveryZone::class, 'parent_id');
    }}
