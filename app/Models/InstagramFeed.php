<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramFeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'link',
        'status',
        'sort_order',
    ];

    /**
     * Scope for active feeds
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }
}
