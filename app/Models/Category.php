<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use SoftDeletes;

    /** The shop sells sauces only; this is the one category all products belong to. */
    public const SAUCE_SLUG = 'sauce';

    protected $appends = ['responsive_image'];

    protected $fillable = ["name","slug", "status", "image","icon", "order","parent_id"];

    public static function sauce(): self
    {
        $category = static::withTrashed()->where('slug', self::SAUCE_SLUG)->first();
        if ($category?->trashed()) {
            $category->restore();
        }

        return $category ?? static::create(['name' => 'Sauce', 'status' => 'active', 'order' => 0]);
    }

    public function isFixed(): bool
    {
        return $this->slug === self::SAUCE_SLUG && $this->parent_id === null;
    }


    protected static function boot()
    {
        parent::boot();
        static::creating(function ($category) {
            $category->slug = Str::slug($category->name);
        });
    }

    // Relationship: Parent Category - Load soft-deleted parents for historical data
    public function parentRecursive()
    {
        return $this->belongsTo(Category::class, 'parent_id')->withTrashed()->with('parentRecursive');
    }

    // Relationship: Child Categories (Recursive)
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->with('children');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product');
    }

    public function getResponsiveImageAttribute(): array
    {
        return app(\App\Support\ResponsiveImages::class)->attributes($this->image);
    }
}
