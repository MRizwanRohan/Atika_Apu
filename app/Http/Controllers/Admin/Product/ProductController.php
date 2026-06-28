<?php

namespace App\Http\Controllers\Admin\Product;

use Inertia\Inertia;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use App\Traits\ClearsProductCache;
use App\Models\AttributeValue;
use App\Models\ProductVariation;
use App\Models\VariationAttribute;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    use ClearsProductCache;


    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = $this->productService->getAllProductsForAdmin($request);
        $categories = $this->productService->getAllCategories();

        return Inertia::render('Admin/Product/Index', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $products = $this->productService->getAdminProductsQuery($request)->get();
        $fileName = 'products-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->streamDownload(function () use ($products) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name',
                'Category',
                'SubCategory',
                'SubChildCategory',
                'Brand',
                'Unit',
                'ProductType',
                'Description',
                'SellingType',
                'PurchasePrice',
                'RegularPrice',
                'DiscountPrice',
                'WholesalePrice',
                'MinimumQty',
                'OpeningStock',
                'Weight(Kg)',
            ]);

            foreach ($products as $product) {
                [$category, $subCategory, $subChildCategory] = $this->resolveCategoryLevels($product->category);

                fputcsv($handle, [
                    $product->name,
                    $category,
                    $subCategory,
                    $subChildCategory,
                    $product->brand?->brand_name ?? '',
                    'Piece',
                    $product->type === 'simple' ? 'single' : $product->type,
                    trim(strip_tags($product->short_description ?: $product->description ?: '')),
                    'retail',
                    $product->unit_cost ?? 0,
                    $product->previous_price ?? $product->price ?? 0,
                    $product->price ?? 0,
                    0,
                    0,
                    $product->stock ?? 0,
                    0,
                ]);
            }

            fclose($handle);
        }, $fileName, $headers);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            $attributeValues = $this->productService->getAllAttributesForProduct();
            $categories = $this->productService->getAllCategories();
            $brands = $this->productService->getAllBrands();

            return Inertia::render('Admin/Product/Create', [
                'attributeValues' => $attributeValues,
                'categories' => $categories,
                'brands'=> $brands,
                'returnTo' => $this->getSafeProductIndexReturnUrl($request->query('return_to')),
                'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : [],
            ]);
        } catch (\Exception $e) {
            Log::error('Product Create Page Error: ' . $e->getMessage());
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Unable to load product creation form.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */


    public function store(StoreProductRequest $request)
    {
        try {
            Log::info('Product Store Request Data:', $request->validated());

            $result = $this->productService->storeProduct($request);
            Log::info('Product Store Result:', $result);

            if ($result['success']) {
                // Clear all product caches after successful creation
                $this->clearAllProductCaches();

                return redirect()
                    ->to($this->getSafeProductIndexReturnUrl($request->input('return_to')) ?? route('admin.products.index'))
                    ->with('success', 'Product created successfully!');
            }

            // For business logic errors, return 422 response so Inertia treats it as validation error
            // This way the onError callback will be triggered
            $errorMessage = 'Failed to create product: ' . $result['error'];

            // Check if there's a deleted product conflict
            if (isset($result['deleted_product'])) {
                $deletedInfo = $result['deleted_product'];
                $errorMessage = $result['error'] . "\n\nDeleted Product Details:\n" .
                    "Name: {$deletedInfo['name']}\n" .
                    "Code: {$deletedInfo['product_code']}\n" .
                    "Deleted At: {$deletedInfo['deleted_at']}";
            }

            // Return with errors bag so the frontend can handle it
            return back()
                ->with('error', $errorMessage)
                ->withInput();

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Validation errors are automatically handled by Laravel
            // This will redirect back with errors and old input
            throw $e;
        } catch (\Exception $e) {
            Log::error('Product Store Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'An unexpected error occurred. Please try again.')
                ->withInput();
        }
    }

    private function getSafeProductIndexReturnUrl(?string $returnTo): ?string
    {
        if (!$returnTo) {
            return null;
        }

        $indexPath = parse_url(route('admin.products.index'), PHP_URL_PATH);
        $returnPath = parse_url($returnTo, PHP_URL_PATH);

        if ($returnPath !== $indexPath) {
            return null;
        }

        $returnHost = parse_url($returnTo, PHP_URL_HOST);

        if ($returnHost && $returnHost !== request()->getHost()) {
            return null;
        }

        return $returnTo;
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with(['variations.attributes.value.attribute'])->findOrFail($id);
        //return $product;
        return Inertia::render('Admin/Product/Show', [
            'product' => $product
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Product $product)
    {
        try {
            $attributeValues = $this->productService->getAllAttributesForProduct($product->id);
            $categories = $this->productService->getAllCategories();
            $brands = $this->productService->getAllBrands();

            $product = $product->load(['categories', 'variations.attributes.value.attribute']);

            return Inertia::render('Admin/Product/Edit', [
                'product' => $product,
                'attributeValues' => $attributeValues,
                'categories'=> $categories,
                'brands'=> $brands,
                'returnTo' => $this->getSafeProductIndexReturnUrl($request->query('return_to')),
                'errors' => session('errors') ? session('errors')->getBag('default')->getMessages() : [],
            ]);
        } catch (\Exception $e) {
            Log::error('Product Edit Page Error: ' . $e->getMessage());
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Unable to load product edit form.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            Log::info('Product Update Request Data:', $request->validated());

            $result = $this->productService->updateProduct($request, $product);

            Log::info('Product Update Result:', $result);

            if ($result['success']) {
                // Clear all product caches after successful update
                $this->clearAllProductCaches();

                // Clear specific product cache
                if ($product->slug) {
                    $this->clearSingleProductCache($product->slug);
                }

                return redirect()
                    ->to($this->getSafeProductIndexReturnUrl($request->input('return_to')) ?? route('admin.products.index'))
                    ->with('success', 'Product updated successfully!');
            }

            // For business logic errors, return back with error flash message
            $errorMessage = 'Failed to update product: ' . $result['error'];

            // Check if there's a deleted product conflict
            if (isset($result['deleted_product'])) {
                $deletedInfo = $result['deleted_product'];
                $errorMessage = $result['error'] . "\n\nDeleted Product Details:\n" .
                    "Name: {$deletedInfo['name']}\n" .
                    "Code: {$deletedInfo['product_code']}\n" .
                    "Deleted At: {$deletedInfo['deleted_at']}";
            }

            return back()
                ->with('error', $errorMessage)
                ->withInput();

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Product Update Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return back()
                ->with('error', 'An unexpected error occurred. Please try again.')
                ->withInput();
        }
    }



    public function updateStock(Request $request, $id)
    {
        $request->validate([
            'type' => 'required|in:simple,variable',
            'stock' => 'required_if:type,simple|integer|min:0',
            'variations' => 'required_if:type,variable|array',
            'variations.*.id' => 'required|exists:product_variations,id',
            'variations.*.stock' => 'required|integer|min:0',
        ]);

        $result = $this->productService->updateProductStock($id, $request->all());

        if ($result['success']) {
            $this->clearAllProductCaches();
            return redirect()->back()->with('success', 'Stock updated successfully!');
        }

        return redirect()->back()->with('error', 'Failed to update stock: ' . ($result['error'] ?? 'Unknown error'));
    }


    public function quickEdit(Request $request, $id)

    {
        $request->validate([
            'status' => 'required|in:Published,Unpublished',
            'is_free_delivery' => 'required|boolean',
            'remarks' => 'nullable|in:New,Popular,Trending,Hot,Special'
        ]);

        // Explicitly handle null remarks
        $remarks = $request->filled('remarks') ? $request->remarks : null;

        $result = $this->productService->quickUpdate($id, [
            'status' => $request->status,
            'is_free_delivery' => $request->is_free_delivery,
            'remarks' => $remarks,
        ]);

        if ($result['success']) {
            // Clear all product caches after quick update
            $this->clearAllProductCaches();

            return redirect()->back()->with('success', 'Product updated successfully!');
        }

        return redirect()->back()->with('error', 'An error occurred while updating the product.');
    }


    public function productDescriptionImageUpload(Request $request)
    {
        if ($request->hasFile('upload')) {
            $filePath = ImageHelper::uploadImage($request->file('upload'), 'storage/products/description');
            $url = asset($filePath);

            // Store the image path in the database
            $product = Product::find($request->product_id);
            if ($product) {
                $images = $product->description_images ?? [];
                $images[] = $filePath;
                $product->description_images = $images;
                $product->save();
            }

            return response()->json([
                'fileName' => basename($filePath),
                'uploaded' => 1,
                'url' => $url
            ]);
        }

        return response()->json(['uploaded' => 0, 'error' => ['message' => 'File not uploaded']], 400);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $result = $this->productService->deleteProduct($id);

        if ($result['success']) {
            // Clear all product caches after deletion
            $this->clearAllProductCaches();
        }

        return redirect()->route('admin.products.index')->with(
            $result['success'] ? 'success' : 'error',
            $result['message']
        );
    }



    public function bulkDeleteProducts(Request $request)
    {
        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'confirmation' => 'required|string|in:confirm'
        ]);

        try {
            $result = $this->productService->bulkDeleteProducts($request->product_ids);

            if ($result['success']) {
                // Clear all product caches after bulk deletion
                $this->clearAllProductCaches();
            }

            return redirect()->back()->with($result['success'] ? 'success' : 'error', $result['message']);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
    }



    public function updateOrder(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:products,id',
            'orders.*.order' => 'required|integer',
        ]);

        $result = $this->productService->updateOrder($request->orders);

        if ($result['success']) {
            $this->clearAllProductCaches();
            return redirect()->back()->with('success', 'Order updated successfully!');
        }

        return redirect()->back()->with('error', 'Failed to update order');
    }

    private function resolveCategoryLevels(?Category $category): array
    {
        if (! $category) {
            return ['', '', ''];
        }

        $lineage = [];
        $current = $category;

        while ($current) {
            array_unshift($lineage, $current->name);
            $current = $current->parentRecursive;
        }

        return [
            $lineage[0] ?? '',
            $lineage[1] ?? '',
            $lineage[2] ?? '',
        ];
    }


}
