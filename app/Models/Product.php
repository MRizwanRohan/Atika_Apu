<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'product_code',
        'category_id',
        'brand_id',
        'short_description',
        'description',
        'status',
        'is_daily_product',
        'type',
        'price',
        'previous_price',
        'youtube_video',
        'feature_image',
        'upload_video',
        'gallery_images',
        'product_tags',
        'specification',
        'stock',
        'sold_stock',
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

    protected $appends = ['feature_image_url', 'avg_rating', 'reviews_count'];

    // Load category including soft-deleted for historical records (orders, invoices)
    public function category()
    {
        return $this->belongsTo(Category::class)->withTrashed()->with('parentRecursive');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
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
}
