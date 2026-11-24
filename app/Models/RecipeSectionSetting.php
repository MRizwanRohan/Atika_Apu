<?php

namespace App\Models;

use App\Support\ResponsiveImages;
use Illuminate\Database\Eloquent\Model;

class RecipeSectionSetting extends Model
{
    public const DEFAULT_LABEL = 'Recipe Inspiration';
    public const DEFAULT_TITLE = 'Make every meal extraordinary.';
    public const DEFAULT_DESCRIPTION = 'From quick snacks to main course, our sauces turn every recipe into a masterpiece.';

    protected $fillable = [
        'enabled',
        'label',
        'title',
        'description',
        'banner',
        'banner_link',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /** The section is configured through a single settings row. */
    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'enabled' => true,
            'label' => self::DEFAULT_LABEL,
            'title' => self::DEFAULT_TITLE,
            'description' => self::DEFAULT_DESCRIPTION,
        ]);
    }

    public function getResponsiveBannerAttribute(): array
    {
        return app(ResponsiveImages::class)->attributes($this->banner);
    }
}
