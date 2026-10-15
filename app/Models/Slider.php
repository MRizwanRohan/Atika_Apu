<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $appends = ['responsive_image', 'responsive_mobile_image'];

    protected $fillable = ['image', 'mobile_image', 'Link', 'bg_color'];

    public function getResponsiveImageAttribute(): array
    {
        return app(\App\Support\ResponsiveImages::class)->attributes($this->image);
    }

    public function getResponsiveMobileImageAttribute(): array
    {
        return app(\App\Support\ResponsiveImages::class)->attributes($this->mobile_image);
    }
}
