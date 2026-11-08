<?php

namespace App\Services\ProductGroup;

use App\Contracts\ProductGroupRepositoryInterface;
use App\Models\ProductGroup;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductGroupService
{
    public function __construct(
        protected ProductGroupRepositoryInterface $repository
    ) {}

    public function paginate(int $perPage = 5): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage);
    }

    public function create(array $data): ProductGroup
    {
        return DB::transaction(function () use ($data) {
            $group = $this->repository->create($data);
            DB::afterCommit(fn () => $this->invalidateHomepage());
            return $group;
        });
    }

    public function update(ProductGroup $productGroup, array $data): ProductGroup
    {
        return DB::transaction(function () use ($productGroup, $data) {
            $group = $this->repository->update($productGroup, $data);
            DB::afterCommit(fn () => $this->invalidateHomepage());
            return $group;
        });
    }

    public function delete(ProductGroup $productGroup): bool
    {
        $result = $this->repository->delete($productGroup);
        if ($result) $this->invalidateHomepage();
        return $result;
    }

    public function toggleStatus(ProductGroup $productGroup): bool
    {
        $result = $this->repository->toggleStatus($productGroup);
        if ($result) $this->invalidateHomepage();
        return $result;
    }

    public function searchProducts(string $query): Collection
    {
        return $this->repository->searchProducts($query);
    }

    public function find(int $id): ?ProductGroup
    {
        return $this->repository->find($id);
    }

    private function invalidateHomepage(): void
    {
        Cache::forever('product_groups_cache_version', Cache::get('product_groups_cache_version', 1) + 1);
    }

    public function getAllWithProducts(): Collection
    {
        $productVersion = Product::getCacheVersion();
        $groupVersion = Cache::get('product_groups_cache_version', 1);
        return Cache::remember("homepage.product_groups.p{$productVersion}.g{$groupVersion}", 1800,
            fn () => $this->repository->getAllWithProducts()
        );
    }

    public function homepagePage(int $page = 1): LengthAwarePaginator
    {
        $productVersion = Product::getCacheVersion();
        $groupVersion = Cache::get('product_groups_cache_version', 1);
        $paginator = Cache::remember("homepage.product_groups.p{$productVersion}.g{$groupVersion}.page{$page}", 1800,
            fn () => $this->repository->homepagePage($page)
        );
        return (clone $paginator)->withPath(route('home.product-groups'));
    }
}
