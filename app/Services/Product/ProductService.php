<?php

namespace App\Services\Product;

use App\Models\Brand;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;
use App\Helpers\VideoHelper;
use App\Models\AttributeValue;
use App\Models\ProductVariation;
use App\Models\VariationAttribute;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Campaign;

class ProductService
{
  public function getAdminProductsQuery($request)
  {
    $search = $request->input('search');
    $categoryIds = $request->input('category_ids', []);
    if ($request->filled('category_id')) {
      $categoryIds = array_merge((array)$categoryIds, [$request->input('category_id')]);
    }

    $query = Product::with([
      'categories.parentRecursive',
      'brand',
    ]);

    if ($search) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('id', 'like', "%{$search}%")
          ->orWhere('price', 'like', "%{$search}%")
          ->orWhere('status', 'like', "%{$search}%");
      });
    }

    if (!empty($categoryIds)) {
      $query->whereHas('categories', function ($q) use ($categoryIds) {
        $q->whereIn('categories.id', array_unique($categoryIds));
      });
    }

    return $query->orderBy('order', 'asc')->orderBy('created_at', 'desc');
  }


  public function getAllProductsForAdmin($request)
  {
    $perPage = $request->input('per_page', 50);
    $search = $request->input('search', '');
    $categoryIdsJson = json_encode($request->input('category_ids', [])) . '_' . $request->input('category_id', '');
    $page = $request->input('page', 1);

    // Generate unique cache key based on pagination, search and category
    $version = Product::getCacheVersion();
    $cacheKey = "products.v{$version}.admin.page.{$page}.per_page.{$perPage}.search." . md5($search) . ".category." . md5($categoryIdsJson);

    // Cache for 30 minutes (1800 seconds)
    return Cache::remember($cacheKey, 1800, function () use ($request, $search, $perPage) {
      $query = $this->getAdminProductsQuery($request)->with([
        'categories.parentRecursive',
        'brand',
        'variations.attributes.value' => function($q) {
          $q->withTrashed(); // Load soft-deleted values to check
        },
        'variations.attributes.value.attribute' => function($q) {
          $q->withTrashed(); // Load soft-deleted attributes to check
        },
        'campaigns'
      ])->withAvg('reviews', 'rating')
        ->withCount('reviews');

      // Get paginated results with soft-delete filtering for variations
      $products = $query->paginate($perPage);

      // Filter out deleted variations, but keep simple products and variable products with at least one valid variation
      $products->getCollection()->transform(function ($product) {
        if ($product->type === 'variable') {
          // Filter variations to only show non-deleted ones
          $activeVariations = $product->variations->filter(function ($variation) {
            // Keep variation only if it's not soft-deleted AND all its attributes are not soft-deleted
            if ($variation->trashed()) {
              return false;
            }
            return $variation->attributes->every(function ($attr) {
              return $attr->value && !$attr->value->trashed() && $attr->value->attribute && !$attr->value->attribute->trashed();
            });
          })->values();

          $product->setRelation('variations', $activeVariations);
        }
        // Simple products are always included
        return $product;
      });

      return $products;
    });
  }


  public function getAllProducts()
  {
    $version = Product::getCacheVersion();
    return Cache::remember("products.v{$version}.all", 1800, function () {
      return Product::with(['categories.parentRecursive', 'variations.attributes.value.attribute', 'brand', 'campaigns'])
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->get();
    });
  }


  public function getSingleProduct($slug)
  {
    $version = Product::getCacheVersion();
    return Cache::remember("product.v{$version}.{$slug}", 3600, function () use ($slug) {
      return Product::where('slug', $slug)
        ->with(['categories.parentRecursive', 'variations.attributes.value.attribute', 'brand', 'campaigns'])
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->first();
    });
  }

  public function categorywithproducts(){
    $version = Product::getCacheVersion();
    return Cache::remember("products.v{$version}.category_grouped.cards", 1800, function () {
            // Get root categories that are active, ordered by 'order'
            $categories = Category::where('status', 'active')
                ->orderBy('order')->get(['id', 'parent_id', 'name', 'slug']);
            $children = $categories->groupBy('parent_id');
            $rootCategories = $categories->whereNull('parent_id');

            $grouped = [];

            foreach ($rootCategories as $root) {
                // Get all descendant category IDs (active ones)
                $ids = $this->descendantIdsFromTree($root->id, $children);

                // Fetch products for these categories
                $products = Product::select(Product::CARD_COLUMNS)->whereHas('categories', function($q) use ($ids) {
                        $q->whereIn('categories.id', $ids);
                    })
                    ->where('status', 'Published')
                    ->with(['variations.attributes.value.attribute', 'campaigns'])
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->orderBy('created_at', 'desc')
                    ->take(8)
                    ->get()->each(fn ($product) => $product->setAppends(['avg_rating', 'reviews_count', 'responsive_image']));

                if ($products->isNotEmpty()) {
                    $grouped[] = [
                        'name' => $root->name,
                        'slug' => $root->slug,
                        'products' => $products
                    ];
                }
            }

            // Optional: Include Uncategorized products at the end
            $uncategorized = Product::select(Product::CARD_COLUMNS)->whereDoesntHave('categories')
                ->where('status', 'Published')
                ->with(['variations.attributes.value.attribute'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get()->each(fn ($product) => $product->setAppends(['avg_rating', 'reviews_count', 'responsive_image']));

            if ($uncategorized->isNotEmpty()) {
                $grouped[] = [
                    'name' => 'Uncategorized',
                    'slug' => 'uncategorized',
                    'products' => $uncategorized
                ];
            }

            return $grouped;
        });
  }

  public function latestProducts(int $limit = 15)
  {
    $version = Product::getCacheVersion();
    return Cache::remember("products.v{$version}.latest.cards.{$limit}", 1800, function () use ($limit) {
        return Product::select(Product::CARD_COLUMNS)
            ->where('status', 'Published')
            ->with(['variations.attributes.value.attribute', 'campaigns'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()->each(fn ($product) => $product->setAppends(['avg_rating', 'reviews_count', 'responsive_image']));
    });
  }


      /**
     * Get active campaigns with products
     */
    public function getActiveCampaigns()
    {
        $campaigns = Campaign::active()
            ->with([
                'products', 
                'products.categories.parentRecursive', 
                'products.variations', 
                'products.variations.attributes.value.attribute', 
                'products.campaigns'
            ])
            ->get();

        // Efficiently load average rating and review count for campaign products
        $campaigns->each(function($campaign) {
            $campaign->products->loadAvg('reviews', 'rating');
            $campaign->products->loadCount('reviews');
        });

        // Calculate time remaining for each campaign
        $campaigns->transform(function ($campaign) {
            $endDate = \Carbon\Carbon::parse($campaign->end_date);
            $now = now();
            $timeRemaining = $endDate->diffInSeconds($now, false); // false to get negative if past

            $campaign->time_remaining = max(0, $timeRemaining); // Ensure non-negative
            return $campaign;
        });

        return $campaigns;
    }



  public function getAllAttributes()
  {
    return Cache::remember('attributes.all', 3600, function () {
      $attributeValues = AttributeValue::whereHas('attribute', function ($q) {
          $q->whereNull('product_id');
        })
        ->with('attribute')
        ->get()
        ->groupBy('attribute.name')
        ->map(function ($values, $attributeName) {
          return [
            'attribute_name' => $attributeName,
            'values' => $values->map(function ($value) {
              return [
                'id' => $value->id,
                'value' => $value->value,
              ];
            })->sortBy('value')->values(), // Sort values alphabetically
          ];
        })
        ->sortBy('attribute_name') // Sort attributes alphabetically
        ->values();

      return $attributeValues;
    });
  }

  public function getAllAttributesForProduct($productId = null)
  {
    $query = Attribute::with(['values' => function($q) {
        $q->orderBy('value', 'asc');
    }]);

    if ($productId) {
        $query->where(function($q) use ($productId) {
            $q->whereNull('product_id')
              ->orWhere('product_id', $productId);
        });
    } else {
        $query->whereNull('product_id');
    }

    return $query->get()->map(function ($attribute) {
        return [
            'id' => $attribute->id,
            'attribute_name' => $attribute->name,
            'product_id' => $attribute->product_id,
            'values' => $attribute->values->map(function ($value) {
                return [
                    'id' => $value->id,
                    'value' => $value->value,
                    'color' => $value->color,
                ];
            })->sortBy('value')->values(),
        ];
    })->sortBy('attribute_name')->values();
  }

  /**
   * Get related products for a product (by category fallback). Returns a collection of Product models.
   * @param int $productId
   * @param int $limit
   * @return \Illuminate\Database\Eloquent\Collection
   */
  public function getRelatedProducts($productId, $limit = 10)
  {
    $version = Product::getCacheVersion();
    $cacheKey = "products.v{$version}.related.{$productId}.limit.{$limit}";

    return Cache::remember($cacheKey, 1800, function () use ($productId, $limit) {
      try {
        $product = Product::with('categories')->find($productId);
        if (!$product) {
          return collect();
        }

        // Prefer same category
        $categoryIds = $product->categories->pluck('id');
        if ($categoryIds->isNotEmpty()) {
          $related = Product::whereHas('categories', function($q) use ($categoryIds) {
              $q->whereIn('categories.id', $categoryIds);
            })
            ->where('id', '!=', $productId)
            ->where('status', 'Published')
            ->select(Product::CARD_COLUMNS)
            ->with(['variations.attributes.value.attribute', 'campaigns'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->take($limit)
            ->get();

          if ($related->isNotEmpty()) {
            return $this->prepareRelatedCards($related);
          }
        }

        // Fallback: get products from same brand
        if ($product->brand_id) {
          $related = Product::where('brand_id', $product->brand_id)
            ->where('id', '!=', $productId)
            ->where('status', 'Published')
            ->select(Product::CARD_COLUMNS)
            ->with(['variations.attributes.value.attribute', 'campaigns'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->take($limit)
            ->get();

          if ($related->isNotEmpty()) {
            return $this->prepareRelatedCards($related);
          }
        }

        // Final fallback: latest published products excluding current
        $latest = Product::where('id', '!=', $productId)
          ->where('status', 'Published')
          ->select(Product::CARD_COLUMNS)
          ->with(['variations.attributes.value.attribute', 'campaigns'])
          ->withAvg('reviews', 'rating')
          ->withCount('reviews')
          ->orderBy('created_at', 'desc')
          ->take($limit)
          ->get();
        return $this->prepareRelatedCards($latest);
      } catch (\Exception $e) {
        // On error, return empty collection but don't break the page
        return collect();
      }
    });
  }


  /**
   * Trim related products to what the product card renders: no appended
   * accessors that hit the DB, and a small thumbnail instead of the original.
   */
  private function prepareRelatedCards($products)
  {
    $images = app(\App\Support\ResponsiveImages::class);

    return $products->each(function ($product) use ($images) {
      $product->feature_image = $images->thumbnail($product->feature_image);
      $product->makeHidden(['gallery_images']);
      $product->setAppends(['avg_rating', 'reviews_count']);
    });
  }

  public function getAllCategories()
  {
    return Cache::remember('categories.all', 3600, function () {
      return Category::with('children')->whereNull('parent_id')->get();
    });
  }

  public function getAllBrands()
  {
    return Cache::remember('brands.all', 3600, function () {
      return Brand::all();
    });
  }

  /**
   * Return paginated, filtered products based on the provided params array.
   * Expected keys: search, category, category_ids, brand_ids, min_price, max_price,
   * attributes (array), sort_by, per_page
   */
  public function getFilteredProducts(array $params = [])
  {
    $search = $params['search'] ?? '';
    $categorySlug = $params['category'] ?? '';
    $categoryIds = $params['category_ids'] ?? [];
    $brandIds = $params['brand_ids'] ?? [];
    $minPrice = $params['min_price'] ?? null;
    $maxPrice = $params['max_price'] ?? null;
    $sortBy = $params['sort_by'] ?? 'latest';
    $perPage = $params['per_page'] ?? 40;
    $attributeFilters = $params['attributes'] ?? [];

    $query = Product::where('status', 'Published')
        ->with(['categories', 'brand', 'variations.attributes.value.attribute', 'campaigns'])
        ->withAvg('reviews', 'rating')
        ->withCount('reviews');

    if ($search) {
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('product_code', 'like', "%{$search}%")
          ->orWhere('product_tags', 'like', "%{$search}%")
          ->orWhere('short_description', 'like', "%{$search}%");
      });
    }

    // Category filters
    if ($categorySlug) {
      $category = Category::where('slug', $categorySlug)->first();
      if ($category) {
        $allCategoryIds = $this->getAllDescendantCategoryIds($category->id);
        $query->whereHas('categories', function ($q) use ($allCategoryIds) {
          $q->whereIn('categories.id', $allCategoryIds);
        });
      }
    } elseif (!empty($categoryIds)) {
      $allIds = [];
      foreach ((array) $categoryIds as $catId) {
        $allIds = array_merge($allIds, $this->getAllDescendantCategoryIds($catId));
      }
      $query->whereHas('categories', function ($q) use ($allIds) {
        $q->whereIn('categories.id', array_unique($allIds));
      });
    }

    // Brand
    if (!empty($brandIds)) {
      $query->whereIn('brand_id', (array) $brandIds);
    }

    // Price
    if ($minPrice !== null) {
      $query->where('price', '>=', $minPrice);
    }
    if ($maxPrice !== null) {
      $query->where('price', '<=', $maxPrice);
    }

    // Attribute filters
    if (!empty($attributeFilters)) {
      foreach ($attributeFilters as $attributeName => $values) {
        if (!empty($values)) {
          $query->whereHas('variations.attributes.value', function ($q) use ($attributeName, $values) {
            $q->whereHas('attribute', function ($q2) use ($attributeName) {
              $q2->where('name', $attributeName);
            });
            $q->whereIn('value', (array) $values);
          });
        }
      }
    }

    // Pre-order and Stock-out products always at the end
    $query->orderBy(DB::raw('CASE WHEN stock <= 0 OR is_pre_order = 1 THEN 1 ELSE 0 END'), 'asc');

    // Sorting
    switch ($sortBy) {
      case 'price_asc':
        $query->orderBy('price', 'asc');
        break;
      case 'price_desc':
        $query->orderBy('price', 'desc');
        break;
      case 'name_asc':
        $query->orderBy('name', 'asc');
        break;
      case 'name_desc':
        $query->orderBy('name', 'desc');
        break;
      case 'oldest':
        $query->orderBy('created_at', 'asc');
        break;
      case 'latest':
      default:
        $query->orderBy('created_at', 'desc');
        break;
    }

    return $query->paginate($perPage)->withQueryString();
  }

  /**
   * Return filter options: categories, brands, attributes and price range in the shape used by frontend
   */
  public function getFilterOptions()
  {
    // Categories (root with children)
    $categories = $this->getAllCategories();

    // Brands (active with published product counts)
    $brands = Brand::where('status', 'active')
      ->withCount(['products' => function ($q) {
        $q->where('status', 'Published');
      }])
      ->orderBy('brand_name')
      ->get();

    // Attributes and values (exclude soft-deleted values)
    $attributes = Attribute::with(['values' => function ($q) {
        $q->whereNull('deleted_at');
      }])
      ->whereHas('values')
      ->orderBy('name')
      ->get()
      ->map(function ($attribute) {
        return [
          'id' => $attribute->id,
          'name' => $attribute->name,
          'values' => $attribute->values->map(function ($value) {
            return [
              'id' => $value->id,
              'value' => $value->value,
              'color' => $value->color,
            ];
          })->values()
        ];
      });

    $priceRange = Product::where('status', 'Published')
      ->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
      ->first();

    return [
      'categories' => $categories,
      'brands' => $brands,
      'attributes' => $attributes,
      'priceRange' => [
        'min' => (float) ($priceRange->min_price ?? 0),
        'max' => (float) ($priceRange->max_price ?? 10000),
      ],
    ];
  }

  /**
   * Recursive helper to collect category and descendant ids (active children only)
   */
  private function descendantIdsFromTree($categoryId, $children): array
  {
    $ids = [];
    $pending = [$categoryId];
    while ($pending) {
      $id = array_pop($pending);
      if (isset($ids[$id])) continue;
      $ids[$id] = $id;
      foreach ($children->get($id, collect()) as $child) $pending[] = $child->id;
    }
    return array_values($ids);
  }

  private function getAllDescendantCategoryIds($categoryId)
  {
    $categoryIds = [$categoryId];
    $children = Category::where('parent_id', $categoryId)
      ->where('status', 'active')
      ->pluck('id');

    foreach ($children as $childId) {
      $categoryIds = array_merge(
        $categoryIds,
        $this->getAllDescendantCategoryIds($childId)
      );
    }

    return $categoryIds;
  }


  public function storeProduct($request)
  {
    try {
      DB::beginTransaction();

      // Generate base slug from product name
      $baseSlug = Str::slug($request->name);
      $productCode = $request->product_code;

      Log::info('Product creation attempt:', ['name' => $request->name, 'base_slug' => $baseSlug, 'code' => $productCode]);

      // Check if product_code conflicts with any product (active or soft-deleted)
      // Codes must be unique globally
      $existingProductCode = Product::withTrashed()
        ->where('product_code', $productCode)
        ->first();

      if ($existingProductCode) {
        DB::rollBack();
        $errorMsg = $existingProductCode->trashed()
          ? "This product code was used by a deleted product. Please use a different product code."
          : "This product code already exists. Please use a different product code.";

        Log::info('Product code conflict found:', ['code' => $productCode, 'trashed' => $existingProductCode->trashed()]);
        return [
          'success' => false,
          'error' => $errorMsg,
        ];
      }

      // Handle Image Uploads
      $featureImagePath = $request->hasFile('feature_image')
        ? ImageHelper::uploadImage($request->file('feature_image'), 'storage/products')
        : null;

      $galleryImages = $this->uploadGalleryImages($request);

      Log::info('Gallery Images:', $galleryImages);

            // Handle Video Upload
      $uploadVideoPath = null;
      if ($request->hasFile('upload_video')) {
        $uploadVideoPath = VideoHelper::uploadVideo($request->file('upload_video'), 'storage/products/videos');
      }

      // Calculate total stock
      $totalStock = $this->calculateTotalStock($request);

      // Create Product
      // The prepareProductData will call generateUniqueSlug if needed
      // Since we only check for base slug conflicts, generateUniqueSlug will just return the base slug
      $product = Product::create(
        $this->prepareProductData($request, $featureImagePath, $galleryImages, $totalStock, $uploadVideoPath)
      );

      // Sync category relationship
      $product->categories()->sync($request->category_ids);

      Log::info('Created Product ID: ' . $product);

      // Handle Variations
      if ($request->type === 'variable') {
        $this->storeProductVariations($product, $request);
        Log::info('Stored Variations for product ID: ' . $product->id);
      }

      DB::commit();
      return ['success' => true, 'product' => $product];
    } catch (\Illuminate\Database\QueryException $e) {
      DB::rollBack();

      // Check if it's a unique constraint violation
      if ($e->getCode() == 23000) {
        // Check if constraint is for slug or product_code
        if (strpos($e->getMessage(), 'slug') !== false) {
          return [
            'success' => false,
            'error' => "A product with this name already exists. Please use a different name.",
          ];
        }

        if (strpos($e->getMessage(), 'product_code') !== false) {
          return [
            'success' => false,
            'error' => "This product code already exists. Please use a different product code.",
          ];
        }
      }

      return ['success' => false, 'error' => $e->getMessage()];
    } catch (\Exception $e) {
      DB::rollBack();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }



  private function uploadGalleryImages($request)
  {
    $galleryImages = [];
    if ($request->hasFile('gallery_images')) {
      foreach ($request->file('gallery_images') as $image) {
        $galleryImages[] = ImageHelper::uploadImage($image, 'storage/products/gallery');
      }
    }
    return $galleryImages;
  }



  private function calculateTotalStock($request)
  {
    if ($request->type === 'variable' && $request->has('variations')) {
      return array_sum(array_map(fn($v) => $v['stock'] ?? 0, $request->variations));
    }
    return $request->input('stock', 0);
  }



  private function processCustomAttributes($product, $customAttributes)
  {
    $tempValueIdMap = [];
    $activeAttributeIds = [];

    foreach ($customAttributes as $attrData) {
      $attributeName = strtoupper(trim($attrData['name']));

      // 1. Find or create custom Attribute
      if (!empty($attrData['id'])) {
        $attribute = Attribute::where('product_id', $product->id)->findOrFail($attrData['id']);
        $attribute->update(['name' => $attributeName]);
      } else {
        $attribute = Attribute::create([
          'name' => $attributeName,
          'product_id' => $product->id,
        ]);
      }
      $activeAttributeIds[] = $attribute->id;

      $activeValueIds = [];
      // 2. Process Values for this custom attribute
      if (isset($attrData['values']) && is_array($attrData['values'])) {
        foreach ($attrData['values'] as $valData) {
          $valueText = strtoupper(trim($valData['value']));

          if (!empty($valData['id'])) {
            // Keep / update existing value
            $value = AttributeValue::withTrashed()
              ->where('attribute_id', $attribute->id)
              ->findOrFail($valData['id']);

            if ($value->trashed()) {
              $value->restore();
            }

            $value->update([
              'value' => $valueText,
              'color' => $valData['color'] ?? null,
            ]);
            $activeValueIds[] = $value->id;
          } else {
            // Create new value (could have been soft deleted previously)
            $value = AttributeValue::withTrashed()
              ->where('attribute_id', $attribute->id)
              ->where('value', $valueText)
              ->first();

            if ($value) {
              if ($value->trashed()) {
                $value->restore();
              }
              $value->update(['color' => $valData['color'] ?? null]);
            } else {
              $value = AttributeValue::create([
                'attribute_id' => $attribute->id,
                'value' => $valueText,
                'color' => $valData['color'] ?? null,
              ]);
            }

            $activeValueIds[] = $value->id;

            // Map frontend temp_id to real database id for variations mapping
            if (!empty($valData['temp_id'])) {
              $tempValueIdMap[$valData['temp_id']] = $value->id;
            }
          }
        }
      }

      // Soft delete custom attribute values that were removed in UI
      AttributeValue::where('attribute_id', $attribute->id)
        ->whereNotIn('id', $activeValueIds)
        ->delete();
    }

    // Soft delete custom attributes that were removed completely from the product
    Attribute::where('product_id', $product->id)
      ->whereNotIn('id', $activeAttributeIds)
      ->delete();

    // Flush cache
    Cache::forget('attributes.all');

    return $tempValueIdMap;
  }

  private function storeProductVariations($product, $request)
  {
    $tempValueIdMap = [];
    if ($request->has('custom_attributes')) {
      $tempValueIdMap = $this->processCustomAttributes($product, $request->custom_attributes);
    }

    if ($request->has('variations')) {
      foreach ($request->variations as $index => $variationData) {
        $variationImagePath = $request->hasFile("variations.$index.image_path")
          ? ImageHelper::uploadImage($request->file("variations.$index.image_path"), 'storage/variations')
          : null;

        $variation = ProductVariation::create([
          'product_id' => $product->id,
          'price' => $variationData['price'],
          // Accept previous_price when provided
          'previous_price' => $variationData['previous_price'] ?? null,
          'stock' => $variationData['stock'] ?? 0,
          'image_path' => $variationImagePath,
        ]);

        if (isset($variationData['attributes'])) {
          foreach ($variationData['attributes'] as $attributeData) {
            $valueId = $attributeData['attribute_value_id'];
            if (isset($tempValueIdMap[$valueId])) {
              $valueId = $tempValueIdMap[$valueId];
            }

            VariationAttribute::create([
              'product_variation_id' => $variation->id,
              'attribute_value_id' => $valueId,
            ]);
          }
        }
      }
    }
  }




  // Product Service update method
  public function updateProduct($request, Product $product)
  {
    try {
      DB::beginTransaction();

      \Illuminate\Support\Facades\Log::info('ProductService: Starting update for product ' . $product->id);

      // Check if product_code is changing and conflicts
      $newProductCode = $request->product_code;

      if ($newProductCode !== $product->product_code) {
        $existingCode = Product::withTrashed()
          ->where('id', '!=', $product->id)
          ->where('product_code', $newProductCode)
          ->first();

        if ($existingCode) {
          DB::rollBack();
          $errorMsg = $existingCode->trashed()
            ? "This product code was used by a deleted product. Please use a different product code."
            : "This product code already exists. Please use a different product code.";

          Log::info('Product code conflict found on update:', ['code' => $newProductCode, 'trashed' => $existingCode->trashed()]);
          return [
            'success' => false,
            'error' => $errorMsg,
          ];
        }
      }

      // Handle Image Uploads
      if ($request->hasFile('feature_image')) {
        // Delete old feature image using helper
        ImageHelper::deleteImage($product->feature_image);

        $featureImagePath = ImageHelper::uploadImage($request->file('feature_image'), 'storage/products');
      } else {
        $featureImagePath = $product->feature_image;
      }

      $galleryImages = $this->updateGalleryImages($request, $product);

      // Calculate total stock
      $totalStock = $this->calculateTotalStock($request);

      $uploadVideoPath = null;
      if ($request->hasFile('upload_video')) {
        // Delete old video using helper
        VideoHelper::deleteVideo($product->upload_video);

        $uploadVideoPath = VideoHelper::uploadVideo($request->file('upload_video'), 'storage/products/videos');
      } else {
        $uploadVideoPath = $product->upload_video;
      }

      // Update Product
      $product->update(
        $this->prepareProductData($request, $featureImagePath, $galleryImages, $totalStock, $uploadVideoPath, $product->id)
      );

      // Sync category relationship
      $product->categories()->sync($request->category_ids);

      // Handle Variations
      if ($request->type === 'variable') {
        \Illuminate\Support\Facades\Log::info('ProductService: Updating variations');
        $this->updateProductVariations($product, $request);
      } else {
        // If switched to simple product, remove existing variations
        $product->variations()->delete();
      }

      DB::commit();
      return ['success' => true, 'product' => $product->fresh()];
    } catch (\Illuminate\Database\QueryException $e) {
      DB::rollBack();

      // Check if it's a unique constraint violation
      if ($e->getCode() == 23000) {
        if (strpos($e->getMessage(), 'slug') !== false) {
          return [
            'success' => false,
            'error' => "A product with this name already exists. Please use a different name.",
          ];
        }

        if (strpos($e->getMessage(), 'product_code') !== false) {
          return [
            'success' => false,
            'error' => "This product code already exists. Please use a different product code.",
          ];
        }
      }

      return ['success' => false, 'error' => $e->getMessage()];
    } catch (\Exception $e) {
      DB::rollBack();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }

  /**
   * Generate a unique slug for the product
   * If slug exists (including soft-deleted), append a number
   */
  private function generateUniqueSlug($name, $excludeId = null)
  {
    $slug = Str::slug($name);
    $originalSlug = $slug;
    $count = 1;

    // Check if slug exists (including soft-deleted products)
    while (true) {
      $query = Product::withTrashed()->where('slug', $slug);

      if ($excludeId) {
        $query->where('id', '!=', $excludeId);
      }

      if (!$query->exists()) {
        break;
      }

      $slug = $originalSlug . '-' . $count;
      $count++;
    }

    return $slug;
  }


    private function prepareProductData($request, $featureImagePath, $galleryImages, $totalStock, $uploadVideoPath = null, $productId = null)
  {
    return [
      'name' => $request->name,
      'slug' => $this->generateUniqueSlug($request->name, $productId),
      'product_code' => $request->product_code,
      'brand_id' => $request->brand_id,
      'short_description' => $request->short_description,
      'description' => $request->description,
      'status' => $request->status ?? 'Published',
      'is_daily_product' => $request->is_daily_product ?? 0,
      'is_pre_order' => $request->is_pre_order ?? 0,
      'type' => $request->type ?? 'simple',
      'price' => $request->price,
      'previous_price' => $request->previous_price ?? null,
      'youtube_video' => $request->youtube_video ?? null,
      'feature_image' => $featureImagePath,
      'gallery_images' => $galleryImages,
      'product_tags' => $request->product_tags ?? [],
      'specification' => $request->specification ?? [],
      'stock' => $totalStock,
      'fake_sold_count' => $request->fake_sold_count ?? 0,
      'is_free_delivery' => $request->is_free_delivery ?? 0,
      'remarks' => $request->remarks,
      'meta_title' => $request->meta_title,
      'meta_description' => $request->meta_description,
      'upload_video' => $uploadVideoPath,
    ];
  }

  private function updateGalleryImages($request, Product $product)
  {
    $existingGalleryImages = is_array($product->gallery_images) ? $product->gallery_images : [];
    $deletedGalleryImages = $request->input('deleted_gallery_images', []);

    if (!is_array($deletedGalleryImages)) {
      $deletedGalleryImages = [];
    }

    if ($request->has('existing_gallery_images')) {
      $keptGalleryImages = $request->input('existing_gallery_images', []);
    } else {
      $keptGalleryImages = array_values(array_diff($existingGalleryImages, $deletedGalleryImages));
    }

    if (!is_array($keptGalleryImages)) {
      $keptGalleryImages = $existingGalleryImages;
    }

    // Keep only images that already belong to this product.
    $galleryImages = array_values(array_filter(
      $keptGalleryImages,
      fn($image) => in_array($image, $existingGalleryImages, true)
    ));

    $deletedGalleryImages = array_unique(array_merge(
      $deletedGalleryImages,
      array_values(array_diff($existingGalleryImages, $galleryImages))
    ));

    foreach ($deletedGalleryImages as $deletedImage) {
      if (in_array($deletedImage, $existingGalleryImages, true)) {
        ImageHelper::deleteImage($deletedImage);
      }
    }

    if ($request->hasFile('gallery_images')) {
      foreach ($request->file('gallery_images') as $image) {
        $galleryImages[] = ImageHelper::uploadImage($image, 'storage/products/gallery');
      }
    }

    return array_values($galleryImages);
  }


  private function updateProductVariations($product, $request)
  {
    $tempValueIdMap = [];
    if ($request->has('custom_attributes')) {
      $tempValueIdMap = $this->processCustomAttributes($product, $request->custom_attributes);
    }

    // Get existing variations as a collection indexed by ID for easy lookup
    $existingVariations = $product->variations->keyBy('id')->toArray();
    $updatedVariationIds = [];

    // Process variations from the request
    if ($request->has('variations')) {
      foreach ($request->variations as $index => $variationData) {
        $variationId = $variationData['id'] ?? null;

        // Map temp value IDs if any
        $mappedAttributes = [];
        if (isset($variationData['attributes']) && is_array($variationData['attributes'])) {
          foreach ($variationData['attributes'] as $attrData) {
            $valueId = $attrData['attribute_value_id'];
            if (isset($tempValueIdMap[$valueId])) {
              $valueId = $tempValueIdMap[$valueId];
            }
            $mappedAttributes[] = $valueId;
          }
        }

        if ($variationId && isset($existingVariations[$variationId])) {
          // Update existing variation
          $variation = ProductVariation::find($variationId);
          $existingImagePath = $variation->image_path;

          // Only update image_path if a new file is uploaded
          if ($request->hasFile("variations.$index.image_path")) {
            // Delete old variation image using helper
            ImageHelper::deleteImage($existingImagePath);

            $variationImagePath = ImageHelper::uploadImage($request->file("variations.$index.image_path"), 'storage/variations');
          } else {
            $variationImagePath = $existingImagePath;
          }

          $variation->update([
            'price' => $variationData['price'],
            'previous_price' => $variationData['previous_price'] ?? null,
            'stock' => $variationData['stock'] ?? 0,
            'image_path' => $variationImagePath,
          ]);

          // Sync variation attributes instead of deleting all
          $currentValueIds = $variation->attributes()->pluck('attribute_value_id')->toArray();
          
          // Delete missing ones
          $variation->attributes()->whereNotIn('attribute_value_id', $mappedAttributes)->delete();
          // Add new ones
          foreach ($mappedAttributes as $valueId) {
            if (!in_array($valueId, $currentValueIds)) {
              VariationAttribute::create([
                'product_variation_id' => $variation->id,
                'attribute_value_id' => $valueId,
              ]);
            }
          }

          $updatedVariationIds[] = $variationId;
        } else {
          // Create new variation
          $variationImagePath = $request->hasFile("variations.$index.image_path")
            ? ImageHelper::uploadImage($request->file("variations.$index.image_path"), 'storage/variations')
            : null;

          $variation = ProductVariation::create([
            'product_id' => $product->id,
            'price' => $variationData['price'],
            'previous_price' => $variationData['previous_price'] ?? null,
            'stock' => $variationData['stock'] ?? 0,
            'image_path' => $variationImagePath,
          ]);

          foreach ($mappedAttributes as $valueId) {
            VariationAttribute::create([
              'product_variation_id' => $variation->id,
              'attribute_value_id' => $valueId,
            ]);
          }

          $updatedVariationIds[] = $variation->id;
        }
      }
    }

    // Only delete variations that were explicitly removed by user
    // (i.e., they existed in DB but were not included in the request)
    if (!empty($updatedVariationIds)) {
      $product->variations()
        ->whereNotIn('id', $updatedVariationIds)
        ->delete();
    }
  }



public function quickUpdate($productId, array $data)
{
    try {
        $product = Product::findOrFail($productId);

        $updateData = [
            'status' => $data['status'] ?? $product->status,
            'is_free_delivery' => $data['is_free_delivery'] ?? $product->is_free_delivery,
        ];

        // ✅ Important fix: use array_key_exists to preserve null
        if (array_key_exists('remarks', $data)) {
            $updateData['remarks'] = $data['remarks']; // will update to NULL if null
        }

        $product->update($updateData);

        return [
            'success' => true,
            'message' => 'Product updated successfully',
            'product' => $product
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to update product: ' . $e->getMessage()
        ];
    }
}




  public function deleteProduct($id)
  {
    try {
      DB::beginTransaction();

      // Find the product
      $product = Product::findOrFail($id);

      // Soft delete variations (keep images for order history)
      foreach ($product->variations as $variation) {
        VariationAttribute::where('product_variation_id', $variation->id)->delete();
        $variation->delete();
      }

      // Soft delete product (keeps all data for orders and inventory tracking)
      $product->delete();

      DB::commit();

      return ['success' => true, 'message' => 'Product deleted successfully! (Data preserved for orders and inventory)'];
    } catch (\Exception $e) {
      DB::rollBack();
      return ['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()];
    }
  }



  public function bulkDeleteProducts(array $productIds)
    {
        DB::beginTransaction();

        try {
            $products = Product::whereIn('id', $productIds)->get();

            foreach ($products as $product) {
                // Soft delete variations (keep images for order history)
                foreach ($product->variations as $variation) {
                    VariationAttribute::where('product_variation_id', $variation->id)->delete();
                    $variation->delete();
                }

                // Soft delete product (keeps all data for orders and inventory tracking)
                /** @var Product $product */
                $product->delete();
            }

            DB::commit();
            return ['success' => true, 'message' => 'Selected products deleted successfully! (Data preserved for orders and inventory)'];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }






  public function updateOrder($orders)
  {
    try {
      DB::beginTransaction();
      foreach ($orders as $item) {
        Product::where('id', $item['id'])->update(['order' => $item['order']]);
      }
      DB::commit();
      return ['success' => true];
    } catch (\Exception $e) {
      DB::rollBack();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }


  public function updateProductStock($productId, $data)
  {
    try {
      DB::beginTransaction();

      $product = Product::findOrFail($productId);

      if ($data['type'] === 'simple') {
        $product->update(['stock' => $data['stock']]);
      } else {
        foreach ($data['variations'] as $vData) {
          ProductVariation::where('id', $vData['id'])
            ->where('product_id', $productId)
            ->update(['stock' => $vData['stock']]);
        }
        
        // Refresh total stock for variable product
        $totalStock = ProductVariation::where('product_id', $productId)->sum('stock');
        $product->update(['stock' => $totalStock]);
      }

      DB::commit();
      return ['success' => true];
    } catch (\Exception $e) {
      DB::rollBack();
      return ['success' => false, 'error' => $e->getMessage()];
    }
  }


}
