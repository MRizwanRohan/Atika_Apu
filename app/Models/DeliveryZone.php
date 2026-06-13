<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    public const TYPE_DISTRICT = 'district';
    public const TYPE_THANA = 'upazila';

    protected $fillable = [
        'type',
        'name',
        'bn_name',
        'json_id',
        'parent_id',
        'charge',
        'is_active',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(DeliveryZone::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'parent_id');
    }
}
