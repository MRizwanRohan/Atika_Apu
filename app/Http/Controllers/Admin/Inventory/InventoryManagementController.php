<?php

namespace App\Http\Controllers\Admin\Inventory;

use Inertia\Inertia;
use App\Models\Product;
use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Order\OrderInterface;

class InventoryManagementController extends Controller
{

    protected $orderService;

    public function __construct(OrderInterface $orderService)
    {
        $this->orderService = $orderService;
    }


public function generateReport(Request $request)
{
    $filters = [
        'status' => $request->input('status'),
        'payment_status' => $request->input('payment_status'),
        'date_from' => $request->input('date_from'),
        'date_to' => $request->input('date_to'),
        'customer_search' => $request->input('customer_search'),
        'min_total' => $request->input('min_total'),
        'max_total' => $request->input('max_total'),
        'order_number' => $request->input('order_number'),
    ];

    $perPage = (int) $request->input('per_page', 10);
    $sortBy = $request->input('sort_by', 'created_at');
    $sortDirection = $request->input('sort_direction', 'desc');

    $orders = $this->orderService->getOrders([
        'per_page' => $perPage,
        'filters' => $filters,
        'sort_by' => $sortBy,
        'sort_direction' => $sortDirection,
    ]);

    // Fetch products with their relationships
    // Load WITH soft-deleted attribute values so we can filter them out from stock UI
    $products = Product::with([
        'category',
        'variations.attributes.value' => function($query) {
            $query->withTrashed(); // Load soft-deleted attribute values
        },
        'variations.attributes.value.attribute' => function($query) {
            $query->withTrashed(); // Load soft-deleted attributes
        }
    ])->get();

    // Filter out variations that have any soft-deleted attribute values (for stock UI only)
    $products->each(function ($product) {
        if ($product->type === 'variable') {
            $filteredVariations = $product->variations->filter(function ($variation) {
                // Keep only variations where ALL attribute values are NOT soft-deleted
                return $variation->attributes->every(function ($attr) {
                    return $attr->value && !$attr->value->trashed();
                });
            })->values(); // Re-index the collection

            $product->setRelation('variations', $filteredVariations);
        }
    });

    // Calculate overall inventory statistics
    $totalProducts = $products->count();
    $totalVariations = $products->flatMap->variations->count();

    // Calculate stock for both simple and variable products
    $totalStock = $products->sum(function ($product) {
        if ($product->type === 'simple') {
            return $product->stock;
        } else {
            return $product->variations->sum('stock');
        }
    });

    // Calculate sold stock for both simple and variable products
    $totalSold = $products->sum(function ($product) {
        if ($product->type === 'simple') {
            return $product->sold_stock;
        } else {
            return $product->variations->sum('sold_stock');
        }
    });

    // Calculate total inventory value
    $totalValue = $products->sum(function ($product) {
        if ($product->type === 'simple') {
            return $product->stock * $product->price;
        } else {
            return $product->variations->sum(function ($variation) {
                return $variation->stock * $variation->price;
            });
        }
    });

    // Calculate sell-through rate (proportion of inventory sold)
    $overallSellThroughRate = ($totalStock + $totalSold) > 0
        ? ($totalSold / ($totalStock + $totalSold)) * 100
        : 0;

    // Products with low stock (less than 20% remaining)
    $lowStockProducts = $products->filter(function ($product) {
        if ($product->type === 'simple') {
            $totalProductStock = $product->stock;
            $totalProductSold = $product->sold_stock;
        } else {
            $totalProductStock = $product->variations->sum('stock');
            $totalProductSold = $product->variations->sum('sold_stock');
        }

        $totalInventory = $totalProductStock + $totalProductSold;

        // Skip products with no inventory
        if ($totalInventory == 0) {
            return false;
        }

        $remainingPercentage = ($totalProductStock / $totalInventory) * 100;
        return $remainingPercentage < 20;
    });

    // Group products by category for analysis
    $productsByCategory = $products->groupBy('category.name')->map(function ($categoryProducts) {
        $categoryStock = $categoryProducts->sum(function ($product) {
            if ($product->type === 'simple') {
                return $product->stock;
            } else {
                return $product->variations->sum('stock');
            }
        });

        $categorySold = $categoryProducts->sum(function ($product) {
            if ($product->type === 'simple') {
                return $product->sold_stock;
            } else {
                return $product->variations->sum('sold_stock');
            }
        });

        $categoryValue = $categoryProducts->sum(function ($product) {
            if ($product->type === 'simple') {
                return $product->stock * $product->price;
            } else {
                return $product->variations->sum(function ($variation) {
                    return $variation->stock * $variation->price;
                });
            }
        });

        $sellThroughRate = 0;
        if (($categoryStock + $categorySold) > 0) {
            $sellThroughRate = ($categorySold / ($categoryStock + $categorySold)) * 100;
        }

        return [
            'total_products' => $categoryProducts->count(),
            'total_variations' => $categoryProducts->flatMap->variations->count(),
            'stock' => $categoryStock,
            'sold' => $categorySold,
            'inventory_value' => $categoryValue,
            'sell_through_rate' => $sellThroughRate,
        ];
    });

    // Get top performing products (highest sell-through rate)
    $topPerformers = $products->filter(function ($product) {
        if ($product->type === 'simple') {
            $totalProductStock = $product->stock;
            $totalProductSold = $product->sold_stock;
        } else {
            $totalProductStock = $product->variations->sum('stock');
            $totalProductSold = $product->variations->sum('sold_stock');
        }
        return ($totalProductStock + $totalProductSold) > 0;
    })->map(function ($product) {
        if ($product->type === 'simple') {
            $totalProductStock = $product->stock;
            $totalProductSold = $product->sold_stock;
        } else {
            $totalProductStock = $product->variations->sum('stock');
            $totalProductSold = $product->variations->sum('sold_stock');
        }

        $sellThroughRate = ($totalProductSold / ($totalProductStock + $totalProductSold)) * 100;

        return [
            'id' => $product->id,
            'name' => $product->name,
            'category' => $product->category->name ?? 'Uncategorized',
            'stock' => $totalProductStock,
            'sold' => $totalProductSold,
            'sell_through_rate' => $sellThroughRate,
        ];
    })->sortByDesc('sell_through_rate')->take(5);

    // return [
    //     'orders' => $orders,
    //     'totalProducts' => $totalProducts,
    //     'totalVariations' => $totalVariations,
    //     'totalStock' => $totalStock,
    //     'totalSold' => $totalSold,
    //     'totalValue' => $totalValue,
    //     'overallSellThroughRate' => $overallSellThroughRate,
    //     'lowStockProducts' => $lowStockProducts,
    //     'productsByCategory' => $productsByCategory,
    //     'topPerformers' => $topPerformers,
    // ];

    return Inertia::render('Admin/Reports/Index', [
        'orders' => $orders,
        'totalProducts' => $totalProducts,
        'totalVariations' => $totalVariations,
        'totalStock' => $totalStock,
        'totalSold' => $totalSold,
        'totalValue' => $totalValue,
        'overallSellThroughRate' => $overallSellThroughRate,
        'lowStockProducts' => $lowStockProducts,
        'productsByCategory' => $productsByCategory,
        'topPerformers' => $topPerformers,
    ]);

}






    /**
     * Get comprehensive stock data for all products with variations
     *
     *
     */
    public function getAllProductsStock()
    {
        // Correct column names for relationships
        $allProductsStock = [];

        Product::with([
            'category:id,name',
            'variations:id,product_id,price,stock,sold_stock,image_path',
            'variations.attributes:id,product_variation_id,attribute_value_id',
            'variations.attributes.value' => function($query) {
                $query->withTrashed()->select(['id', 'attribute_id', 'value', 'deleted_at']);
            },
            'variations.attributes.value.attribute' => function($query) {
                $query->withTrashed()->select(['id', 'name', 'deleted_at']);
            }
        ])
            ->select(['id', 'name', 'stock', 'sold_stock', 'feature_image'])
            ->chunk(100, function ($products) use (&$allProductsStock) {
                foreach ($products as $product) {
                    // Pre-calculate product stock values
                    $initial_stock = $product->stock + $product->sold_stock;
                    $stockData = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_image' => $product->feature_image,
                        'total_stock' => $product->stock,
                        'total_sold' => $product->sold_stock,
                        'initial_stock' => $initial_stock,
                        //   'remaining_stock' => $product->stock - $product->sold_stock,
                        'stock_ratio' => $initial_stock > 0
                            ? round(($product->sold_stock / $initial_stock) * 100, 2)
                            : 0,
                        'variations' => []
                    ];

                    // Process variations - SKIP variations with soft-deleted attribute values
                    foreach ($product->variations as $variation) {
                        // Check if ANY attribute value is soft-deleted
                        $hasSoftDeletedAttribute = $variation->attributes->contains(function ($item) {
                            return !$item->value || $item->value->trashed() || !$item->value->attribute || $item->value->attribute->trashed();
                        });

                        // Skip this variation if it has soft-deleted attributes
                        if ($hasSoftDeletedAttribute) {
                            continue;
                        }

                        $variationTotal = $variation->stock + $variation->sold_stock;

                        // Map attributes correctly
                        $attributeDetails = collect($variation->attributes)
                            ->mapWithKeys(function ($item) {
                                return [
                                    $item->value->attribute->name => $item->value->value
                                ];
                            })
                            ->toArray();

                        $stockData['variations'][] = [
                            'variation_id' => $variation->id,
                            'attributes' => $attributeDetails,
                            'current_stock' => $variation->stock,
                            'sold_stock' => $variation->sold_stock,
                            'initial_stock' => $variationTotal,
                            // 'remaining_stock' => $variation->stock - $variation->sold_stock,
                            'sold_ratio' => $variationTotal > 0
                                ? round(($variation->sold_stock / $variationTotal) * 100, 2)
                                : 0,
                            'price' => $variation->price,
                            'image' => $variation->image_path
                        ];
                    }

                    $allProductsStock[] = $stockData;
                }
            });

        //return $allProductsStock ?? [];

        return Inertia::render('Admin/Inventory/InventoryManagement', [
            'allProductsStock' => $allProductsStock,
        ]);
    }

    /**
     * Get all out-of-stock products and variations
     *
     * @return array Collection of out-of-stock product data
     */
    public function getAllOutStock()
    {
        $outOfStockProducts = [];

        Product::with([
            'category:id,name',
            'variations:id,product_id,price,stock,sold_stock,image_path',
            'variations.attributes:id,product_variation_id,attribute_value_id',
            'variations.attributes.value:id,attribute_id,value',
            'variations.attributes.value.attribute:id,name'
        ])
            ->where('stock', 0) // Get products with zero stock
            ->orWhereHas('variations', function ($query) {
                $query->where('stock', 0); // Also include products where variations are out of stock
            })
            ->select(['id', 'name', 'stock', 'sold_stock'])
            ->chunk(100, function ($products) use (&$outOfStockProducts) {
                foreach ($products as $product) {
                    $stockData = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'total_stock' => $product->stock,
                        'total_sold' => $product->sold_stock,
                        'initial_stock' => $product->stock + $product->sold_stock,
                        'remaining_stock' => 0,
                        'variations' => []
                    ];

                    foreach ($product->variations as $variation) {
                        if ($variation->stock == 0) { // Only include out-of-stock variations
                            $attributeDetails = collect($variation->attributes)
                                ->filter(function ($item) {
                                    return $item->value && $item->value->attribute;
                                })
                                ->mapWithKeys(function ($item) {
                                    return [$item->value->attribute->name => $item->value->value];
                                })
                                ->toArray();

                            $stockData['variations'][] = [
                                'variation_id' => $variation->id,
                                'attributes' => $attributeDetails,
                                'current_stock' => 0,
                                'sold_stock' => $variation->sold_stock,
                                'initial_stock' => $variation->stock + $variation->sold_stock,
                                'sold_ratio' => 100, // Since it's out of stock
                                'price' => $variation->price,
                                'image' => $variation->image_path
                            ];
                        }
                    }

                    if ($stockData['total_stock'] == 0 || count($stockData['variations']) > 0) {
                        $outOfStockProducts[] = $stockData;
                    }
                }
            });

        return $outOfStockProducts ?? [];
    }
    /**
     * Get all products with low stock (less than 20% remaining)
     *
     * @return array Collection of low-stock product data
     */
    public function getAllLowStock()
    {
        $lowStockProducts = [];

        Product::with([
            'category:id,name',
            'variations:id,product_id,price,stock,sold_stock,image_path',
            'variations.attributes:id,product_variation_id,attribute_value_id',
            'variations.attributes.value:id,attribute_id,value',
            'variations.attributes.value.attribute:id,name'
        ])
            ->select(['id', 'name', 'stock', 'sold_stock'])
            ->chunk(100, function ($products) use (&$lowStockProducts) {
                foreach ($products as $product) {
                    $totalProductStock = $product->stock;
                    $totalProductSold = $product->sold_stock;
                    $totalInventory = $totalProductStock + $totalProductSold;

                    // Skip products with no inventory
                    if ($totalInventory == 0) {
                        continue;
                    }

                    $remainingPercentage = ($totalProductStock / $totalInventory) * 100;

                    if ($remainingPercentage < 20) {
                        $stockData = [
                            'product_id' => $product->id,
                            'product_name' => $product->name,
                            'total_stock' => $product->stock,
                            'total_sold' => $product->sold_stock,
                            'initial_stock' => $totalInventory,
                            'remaining_stock' => $product->stock - $product->sold_stock,
                            'variations' => []
                        ];

                        foreach ($product->variations as $variation) {
                            $attributeDetails = collect($variation->attributes)
                                ->filter(function ($item) {
                                    return $item->value && $item->value->attribute;
                                })
                                ->mapWithKeys(function ($item) {
                                    return [$item->value->attribute->name => $item->value->value];
                                })
                                ->toArray();

                            $stockData['variations'][] = [
                                'variation_id' => $variation->id,
                                'attributes' => $attributeDetails,
                                'current_stock' => $variation->stock,
                                'sold_stock' => $variation->sold_stock,
                                'initial_stock' => $variation->stock + $variation->sold_stock,
                                'remaining_stock' => $variation->stock - $variation->sold_stock,
                                'sold_ratio' => round(($variation->sold_stock / ($variation->stock + $variation->sold_stock)) * 100, 2),
                                'price' => $variation->price,
                                'image' => $variation->image_path
                            ];
                        }
                        $lowStockProducts[] = $stockData;
                    }
                }
            });
        return $lowStockProducts ?? [];
    }
}
