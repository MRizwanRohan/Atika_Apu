<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'product_id'];

    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeGlobal($query)
    {
        return $query->whereNull('product_id');
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

}
