<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedProductSetting extends Model
{
    public const DEFAULT_BADGE = 'Our Signature';
    public const DEFAULT_BG_COLOR = '#EFE6D6';

    protected $fillable = [
        'enabled',
        'product_id',
        'badge',
        'description',
        'highlights',
        'bg_color',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'highlights' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** The section is configured through a single settings row. */
    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'enabled' => false,
            'badge' => self::DEFAULT_BADGE,
            'highlights' => [],
            'bg_color' => self::DEFAULT_BG_COLOR,
        ]);
    }
}
