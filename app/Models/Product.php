<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    public const CARD_COLUMNS = [
        'products.id', 'name', 'slug', 'product_code', 'type', 'price', 'previous_price',
        'feature_image', 'gallery_images', 'stock', 'is_pre_order', 'is_free_delivery', 'brand_id',
    ];

    protected $fillable = [
        'name',
        'slug',
        'product_code',
        'brand_id',
        'short_description',
        'description',
        'status',
        'is_daily_product',
        'type',
        'price',
        'unit_cost',
        'previous_price',
        'youtube_video',
        'feature_image',
        'upload_video',
        'gallery_images',
        'product_tags',
        'specification',
        'stock',
        'sold_stock',
        'fake_sold_count',
        'view_count',
        'is_free_delivery',
        'is_pre_order',
        'remarks',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'gallery_images' => 'array',
        'description_images' => 'array',
        'product_tags' => 'array',
        'specification' => 'array',
        'is_free_delivery' => 'boolean',
        'is_pre_order' => 'boolean',
    ];

    protected $appends = ['feature_image_url', 'avg_rating', 'reviews_count', 'category', 'responsive_image'];

    // New Many-to-Many Relationship
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product')->withTrashed()->with('parentRecursive')->withTimestamps();
    }

    // Accessor for backward compatibility (maps product.category to the first category)
    public function getCategoryAttribute()
    {
        if ($this->relationLoaded('categories')) {
            return $this->categories->first();
        }
        return $this->categories()->first();
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class)
            ->withTimestamps();
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function campaigns()
    {
        return $this->belongsToMany(Campaign::class, 'campaign_products')
                    ->withTimestamps();
    }

    public function productGroups(): BelongsToMany
    {
        return $this->belongsToMany(ProductGroup::class, 'product_group_product')
                    ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    /**
     * Get the average rating for the product
     */
    public function getAvgRatingAttribute()
    {
        // Check if avg_rating was eager loaded via withAvg('reviews', 'rating') which defaults to reviews_avg_rating
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            return (float) $this->attributes['reviews_avg_rating'];
        }
        
        // Check if it was alias-loaded as 'avg_rating'
        if (array_key_exists('avg_rating', $this->attributes)) {
            return (float) $this->attributes['avg_rating'];
        }

        return (float) $this->reviews()->avg('rating') ?: 0;
    }

    public function getReviewsCountAttribute()
    {
        // Check if reviews_count was eager loaded via withCount('reviews')
        if (array_key_exists('reviews_count', $this->attributes)) {
            return (int) $this->attributes['reviews_count'];
        }

        return $this->reviews()->count();
    }

    /**
     * Get the gallery images with full URLs
     */
    public function getGalleryImagesUrlsAttribute()
    {
        if (!$this->gallery_images || !is_array($this->gallery_images)) {
            return [];
        }

        return array_map(function ($image) {
            return asset($image);
        }, $this->gallery_images);
    }

    /**
     * Get the feature image with full URL
     */
    public function getFeatureImageUrlAttribute()
    {
        return $this->feature_image ? asset($this->feature_image) : null;
    }

    public static function getCacheVersion()
    {
        return \Illuminate\Support\Facades\Cache::get('products_cache_version', 1);
    }

    public static function incrementCacheVersion()
    {
        $newVersion = static::getCacheVersion() + 1;
        \Illuminate\Support\Facades\Cache::forever('products_cache_version', $newVersion);
        return $newVersion;
    }

    public function getResponsiveImageAttribute(): array
    {
        return app(\App\Support\ResponsiveImages::class)->attributes($this->feature_image);
    }
}
