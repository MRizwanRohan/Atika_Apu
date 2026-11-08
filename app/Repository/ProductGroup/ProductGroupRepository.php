<?php

namespace App\Repository\ProductGroup;

use App\Contracts\ProductGroupRepositoryInterface;
use App\Models\Product;
use App\Models\ProductGroup;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProductGroupRepository implements ProductGroupRepositoryInterface
{
    public function paginate(int $perPage = 10): LengthAwarePaginator
    {
        return ProductGroup::withCount('products')
            ->orderBy('order_number', 'asc')
            ->paginate($perPage);
    }

    public function create(array $data): ProductGroup
    {
        $productGroup = ProductGroup::create($data);

        if (isset($data['product_ids'])) {
            $productGroup->products()->attach($data['product_ids']);
        }

        return $productGroup;
    }

    public function update(ProductGroup $productGroup, array $data): ProductGroup
    {
        $productGroup->update($data);

        if (isset($data['product_ids'])) {
            $productGroup->products()->sync($data['product_ids']);
        }

        return $productGroup;
    }

    public function delete(ProductGroup $productGroup): bool
    {
        return $productGroup->delete();
    }

    public function toggleStatus(ProductGroup $productGroup): bool
    {
        return $productGroup->update([
            'status' => !$productGroup->status
        ]);
    }

    public function searchProducts(string $query): Collection
    {
        return Product::query()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('product_code', 'like', "%{$query}%")
            ->select('id', 'name', 'product_code', 'feature_image', 'price')
            ->limit(10)
            ->get();
    }

    public function find(int $id): ?ProductGroup
    {
        return ProductGroup::with('products')->find($id);
    }

    public function getAllWithProducts(): Collection
    {
        return ProductGroup::with(['products' => function($query) {
                $query->select(Product::CARD_COLUMNS)->where('status', 'Published')
                    ->with(['categories', 'variations.attributes.value.attribute', 'campaigns', 'brand'])
                    ->withAvg('reviews', 'rating')
                    ->withCount('reviews')
                    ->take(10); // Show mapping 10 products
            }])
            ->where('status', true)
            ->orderBy('order_number', 'asc')
            ->get();
    }

    public function homepagePage(int $page): LengthAwarePaginator
    {
        return ProductGroup::with(['products' => function ($query) {
            $query->select(Product::CARD_COLUMNS)->where('status', 'Published')
                ->with(['variations.attributes.value.attribute', 'campaigns'])
                ->withAvg('reviews', 'rating')->withCount('reviews')->take(5);
        }])->where('status', true)->orderBy('order_number')->orderBy('id')
            ->paginate(6, ['id', 'name', 'slug', 'banner', 'order_number'], 'page', $page)
            ->through(function ($group) {
                $group->products->each(fn ($product) => $product->setAppends(['avg_rating', 'reviews_count', 'responsive_image']));
                return $group;
            });
    }
}
