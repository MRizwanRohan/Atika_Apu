<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariation extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'price', 'stock', 'sold_stock', 'image_path'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function attributes()
    {
        return $this->hasMany(VariationAttribute::class);
    }

    public function attributeValues()
    {
        return $this->hasManyThrough(AttributeValue::class, VariationAttribute::class, 'product_variation_id', 'id', 'id', 'attribute_value_id')
            ->withTrashed(); // Load soft-deleted attribute values for historical orders
    }
    public function variationAttributes()
    {
        return $this->hasMany(VariationAttribute::class, 'product_variation_id');
    }

    /**
     * Get the variation image with full URL
     */
    public function getImageUrlAttribute()
    {
        return $this->image_path ? asset($this->image_path) : null;
    }
}
