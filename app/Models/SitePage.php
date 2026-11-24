<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SitePage extends Model
{
    public const FOOTER_GROUPS = ['help', 'about', 'none'];

    protected $fillable = [
        'name',
        'slug',
        'title',
        'status',
        'footer_group',
        'content',
    ];

    protected static function booted(): void
    {
        // The footer's page list is cached; any change to a page must show up right away.
        static::saved(fn () => Cache::forget('site_pages_active'));
        static::deleted(fn () => Cache::forget('site_pages_active'));
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }
}
