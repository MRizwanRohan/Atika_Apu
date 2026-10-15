<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramFeed extends Model
{
    use HasFactory;

    protected $appends = ['responsive_image'];

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

    public function getResponsiveImageAttribute(): array
    {
        return app(\App\Support\ResponsiveImages::class)->attributes($this->image);
    }
}
