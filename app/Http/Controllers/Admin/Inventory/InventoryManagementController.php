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

    // 1. Fetch Paginated Orders
    $orders = $this->orderService->getOrders([
        'per_page' => $perPage,
        'filters' => $filters,
        'sort_by' => $sortBy,
        'sort_direction' => $sortDirection,
    ]);

    // 1b. Calculate Grand Total Revenue (All Filtered Results - Big Tech Standard)
    $grandTotalRevenue = \App\Models\Order::query()
        ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
        ->when($filters['payment_status'], fn($q) => $q->where('payment_status', $filters['payment_status']))
        ->when($filters['date_from'], fn($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
        ->when($filters['date_to'], fn($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
        ->when($filters['customer_search'], function($q) use ($filters) {
            $q->whereHas('customer', function($cq) use ($filters) {
                $cq->where('name', 'like', "%{$filters['customer_search']}%")
                   ->orWhere('email', 'like', "%{$filters['customer_search']}%");
            });
        })
        ->when($filters['min_total'], fn($q) => $q->where('total', '>=', $filters['min_total']))
        ->when($filters['max_total'], fn($q) => $q->where('total', '<=', $filters['max_total']))
        ->when($filters['order_number'], fn($q) => $q->where('order_number', 'like', "%{$filters['order_number']}%"))
        ->sum('total');

    // 2. Extract Inventory Stats (Cached for 10 minutes)
    $report = \Cache::remember('inventory_dashboard_report', 600, function () {
        // Optimized Aggregate Queries
        $simpleStats = \App\Models\Product::where('type', 'simple')
            ->selectRaw('count(*) as count, sum(stock) as stock, sum(sold_stock) as sold, sum(stock * price) as value')
            ->first();

        $variationStats = \App\Models\ProductVariation::selectRaw('count(*) as count, sum(stock) as stock, sum(sold_stock) as sold, sum(stock * price) as value')
            ->first();

        $totalStock = ($simpleStats->stock ?? 0) + ($variationStats->stock ?? 0);
        $totalSold = ($simpleStats->sold ?? 0) + ($variationStats->sold ?? 0);
        $totalValue = ($simpleStats->value ?? 0) + ($variationStats->value ?? 0);
        $totalProducts = \App\Models\Product::count();
        $totalVariations = $variationStats->count ?? 0;

        // Performant Category Breakdown
        $productsByCategory = \App\Models\Category::withCount('products')
            ->get()->mapWithKeys(function ($cat) {
                $ids = \App\Models\Product::where('category_id', $cat->id)->pluck('id');
                $simple = \App\Models\Product::where('category_id', $cat->id)->where('type', 'simple')
                    ->selectRaw('sum(stock) as s, sum(sold_stock) as sl, sum(stock * price) as v')->first();
                $variations = \App\Models\ProductVariation::whereIn('product_id', $ids)
                    ->selectRaw('sum(stock) as s, sum(sold_stock) as sl, sum(stock * price) as v')->first();
                
                $stock = ($simple->s ?? 0) + ($variations->s ?? 0);
                $sold = ($simple->sl ?? 0) + ($variations->sl ?? 0);
                
                return [$cat->name => [
                    'total_products' => $cat->products_count,
                    'stock' => $stock,
                    'sold' => $sold,
                    'inventory_value' => ($simple->v ?? 0) + ($variations->v ?? 0),
                    'sell_through_rate' => ($stock + $sold) > 0 ? ($sold / ($stock + $sold)) * 100 : 0
                ]];
            });

        // Top Performers Strategy (Balanced query/logic)
        $topPerformers = \App\Models\Product::with(['category', 'variations'])->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name ?? 'Uncategorized',
                'stock' => $s = ($p->type === 'simple' ? $p->stock : $p->variations->sum('stock')),
                'sold' => $sl = ($p->type === 'simple' ? $p->sold_stock : $p->variations->sum('sold_stock')),
                'sell_through_rate' => ($s + $sl) > 0 ? ($sl / ($s + $sl)) * 100 : 0,
            ])->sortByDesc('sell_through_rate')->take(5)->values();

        // Low Stock Detection
        $lowStockProducts = \App\Models\Product::with('variations')->get()->filter(function($p) {
            $s = $p->type === 'simple' ? $p->stock : $p->variations->sum('stock');
            $sl = $p->type === 'simple' ? $p->sold_stock : $p->variations->sum('sold_stock');
            return ($s + $sl) > 0 && ($s / ($s + $sl)) < 0.2;
        })->take(10)->values();

        return compact('totalProducts', 'totalVariations', 'totalStock', 'totalSold', 'totalValue', 'productsByCategory', 'topPerformers', 'lowStockProducts');
    });

    $orders->withPath(route('admin.reports.generateReport'));

    return Inertia::render('Admin/Reports/Index', array_merge([
        'orders' => $orders,
        'grandTotalRevenue' => (float) $grandTotalRevenue,
        'overallSellThroughRate' => ($report['totalStock'] + $report['totalSold']) > 0 
            ? ($report['totalSold'] / ($report['totalStock'] + $report['totalSold'])) * 100 
            : 0,
    ], $report));
}

public function exportOrders(Request $request)
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

    $orders = \App\Models\Order::query()
        ->with('customer')
        ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
        ->when($filters['payment_status'], fn($q) => $q->where('payment_status', $filters['payment_status']))
        ->when($filters['date_from'], fn($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
        ->when($filters['date_to'], fn($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
        ->when($filters['customer_search'], function($q) use ($filters) {
            $q->whereHas('customer', function($cq) use ($filters) {
                $cq->where('name', 'like', "%{$filters['customer_search']}%")
                   ->orWhere('email', 'like', "%{$filters['customer_search']}%");
            });
        })
        ->when($filters['min_total'], fn($q) => $q->where('total', '>=', $filters['min_total']))
        ->when($filters['max_total'], fn($q) => $q->where('total', '<=', $filters['max_total']))
        ->when($filters['order_number'], fn($q) => $q->where('order_number', 'like', "%{$filters['order_number']}%"))
        ->latest()
        ->get();

    $fileName = 'orders_full_report_' . date('Y-m-d') . '.csv';
    $headers = [
        "Content-type"        => "text/csv",
        "Content-Disposition" => "attachment; filename=$fileName",
        "Pragma"              => "no-cache",
        "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
        "Expires"             => "0"
    ];

    $callback = function() use($orders) {
        $file = fopen('php://output', 'w');
        fputcsv($file, ['Order #', 'Date', 'Customer Name', 'Customer Phone', 'Status', 'Payment Status', 'Total Amount']);

        $grandTotal = 0;
        foreach ($orders as $order) {
            $row = [
                '#' . $order->order_number,
                $order->created_at->format('Y-m-d H:i'),
                $order->customer_name ?? ($order->customer->name ?? 'N/A'),
                $order->customer_phone ?? ($order->customer->phone ?? 'N/A'),
                $order->status,
                $order->payment_status,
                'BDT ' . $order->total,
            ];
            fputcsv($file, $row);
            $grandTotal += $order->total;
        }

        fputcsv($file, []); // Empty line
        fputcsv($file, ['', '', '', '', '', 'GRAND TOTAL', 'BDT ' . $grandTotal]);
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

public function exportOrdersPDF(Request $request)
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

    $orders = \App\Models\Order::query()
        ->with('customer')
        ->when($filters['status'], fn($q) => $q->where('status', $filters['status']))
        ->when($filters['payment_status'], fn($q) => $q->where('payment_status', $filters['payment_status']))
        ->when($filters['date_from'], fn($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
        ->when($filters['date_to'], fn($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
        ->when($filters['customer_search'], function($q) use ($filters) {
            $q->whereHas('customer', function($cq) use ($filters) {
                $cq->where('name', 'like', "%{$filters['customer_search']}%")
                   ->orWhere('email', 'like', "%{$filters['customer_search']}%");
            });
        })
        ->when($filters['min_total'], fn($q) => $q->where('total', '>=', $filters['min_total']))
        ->when($filters['max_total'], fn($q) => $q->where('total', '<=', $filters['max_total']))
        ->when($filters['order_number'], fn($q) => $q->where('order_number', 'like', "%{$filters['order_number']}%"))
        ->latest()
        ->get();

    $grandTotal = $orders->sum('total');

    // Create a simple HTML view for PDF
    $html = '<html><head><style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; font-size: 12px; }
        th { background-color: #f8f9fa; font-weight: bold; }
        .footer { margin-top: 20px; text-align: right; font-weight: bold; font-size: 16px; border-top: 2px solid #000; padding-top: 10px; }
        .header { text-align: center; margin-bottom: 20px; }
    </style></head><body>';
    
    $html .= '<div class="header"><h1>Inventory Sales Report</h1><p>Full Filtered Report - Generated on: '.date('Y-m-d').'</p></div>';
    
    $html .= '<table><thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Status</th><th>Payment</th><th style="text-align: right;">Total</th></tr></thead><tbody>';
    
    foreach ($orders as $order) {
        $name = $order->customer_name ?? ($order->customer->name ?? 'N/A');
        $phone = $order->customer_phone ?? ($order->customer->phone ?? 'N/A');
        $html .= "<tr>
            <td>#{$order->order_number}</td>
            <td>{$order->created_at->format('M d, Y')}</td>
            <td>{$name}<br><small style='color: #666;'>{$phone}</small></td>
            <td>{$order->status}</td>
            <td>{$order->payment_status}</td>
            <td style='text-align: right; font-weight: bold;'>BDT ".number_format($order->total)."</td>
        </tr>";
    }
    
    $html .= '</tbody></table>';
    $html .= '<div class="footer">GRAND TOTAL: BDT '.number_format($grandTotal).'</div>';
    $html .= '</body></html>';

    $pdf = \App::make('dompdf.wrapper');
    $pdf->loadHTML($html);
    return $pdf->download('full_orders_report_'.date('Y-m-d').'.pdf');
}

    /**
     * Get comprehensive stock data for all products with variations
     *
     *
     */
    /**
     * Get comprehensive stock data for all products with variations
     * Optimized with server-side pagination and search
     */
    public function getAllProductsStock(Request $request)
    {
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');
        $perPage = $request->input('per_page', 10);

        $query = Product::query()
            ->with([
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
            ->select(['id', 'name', 'stock', 'sold_stock', 'feature_image', 'category_id', 'type'])
            ->when($search, function($q) use ($search) {
                $q->where(function($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                      ->orWhere('id', $search);
                });
            })
            ->when($filter === 'low_stock', function($q) {
                $q->where('stock', '<=', 10);
            });

        $paginatedProducts = $query->latest()->paginate($perPage)->withQueryString();

        $paginatedProducts->through(function ($product) {
            $initial_stock = $product->stock + $product->sold_stock;
            
            $stockData = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_image' => $product->feature_image,
                'category' => $product->category->name ?? 'Uncategorized',
                'type' => $product->type,
                'total_stock' => $product->stock,
                'total_sold' => $product->sold_stock,
                'initial_stock' => $initial_stock,
                'stock_ratio' => $initial_stock > 0
                    ? round(($product->sold_stock / $initial_stock) * 100, 2)
                    : 0,
                'variations' => []
            ];

            // Process variations
            foreach ($product->variations as $variation) {
                // Skip variations with soft-deleted attribute values
                $hasSoftDeletedAttribute = $variation->attributes->contains(function ($item) {
                    return !$item->value || $item->value->trashed() || !$item->value->attribute || $item->value->attribute->trashed();
                });

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
                    'sold_ratio' => $variationTotal > 0
                        ? round(($variation->sold_stock / $variationTotal) * 100, 2)
                        : 0,
                    'price' => $variation->price,
                    'image' => $variation->image_path
                ];
            }

            return $stockData;
        });

        return Inertia::render('Admin/Inventory/InventoryManagement', [
            'products' => $paginatedProducts,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage
            ]
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
