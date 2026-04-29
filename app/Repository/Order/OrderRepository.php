<?php

namespace App\Repository\Order;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use App\Repository\Order\OrderRepositoryInterface;


class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Cache TTL in seconds (10 minutes)
     */
    const CACHE_TTL = 600;

    /**
     * Find order by ID with eager loaded items using load optimization
     *
     * @param int $orderId
     * @return Order|null
     */
    public function findWithItems(int $orderId): ?Order
    {
        $cacheKey = "order:{$orderId}:with_items";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($orderId) {
            $order = Order::find($orderId);

            if ($order) {
                $order->load([
                    'orderItems' => function ($query) {
                        $query->select([
                            'id',
                            'order_id',
                            'product_id',
                            'product_variation_id',
                            'quantity',
                            'unit_price',
                            'subtotal',
                            'final_price',
                            'is_pre_order',
                        ]);
                    },
                    'orderItems.productVariation' => function ($query) {
                        $query->select(['id', 'product_id', 'sku', 'price', 'stock_quantity']);
                    },
                    'orderItems.productVariation.variationAttributes' => function ($query) {
                        $query->select(['id', 'product_variation_id', 'attribute_value_id']);
                    },
                    'orderItems.productVariation.variationAttributes.attributeValue' => function ($query) {
                        $query->withTrashed()->select(['id', 'attribute_id', 'value']);
                    },
                    'orderItems.productVariation.variationAttributes.attributeValue.attribute' => function ($query) {
                        $query->withTrashed()->select(['id', 'name']);
                    }
                ]);
            }

            return $order;
        });
    }
    /**
     * Get paginated orders with optimized queries, filtering, and sorting
     *
     * @param int $perPage
     * @param array $filters
     * @param string $sortBy
     * @param string $sortDirection
     * @return LengthAwarePaginator
     */


    // public  function findOrderById(int $orderId);
    public function findOrderById(int $orderId)
    {
        return Order::where('id', $orderId)->first();
    }

public function getPaginatedOrders(
    int $perPage = 10,
    array $filters = [],
    string $sortBy = 'created_at',
    string $sortDirection = 'desc'
): LengthAwarePaginator {
    $cacheVersion = Order::getCacheVersion();
    $page = request()->input('page', 1);
    $path = request()->path(); // Include path to prevent cache collision between different routes
    $cacheKey = "orders:v4:{$cacheVersion}:{$perPage}:{$page}:{$path}:" .
        md5(json_encode($filters)) . ":{$sortBy}:{$sortDirection}";

    return Cache::remember($cacheKey, Order::CACHE_TTL, function () use ($perPage, $filters, $sortBy, $sortDirection) {
        $query = $this->buildOrderQuery($filters, $sortBy, $sortDirection);

        return $query->with([
            'items' => function ($query) {
                $query->select([
                    'id',
                    'order_id',
                    'product_id',
                    'product_variation_id',
                    'quantity',
                    'returned_quantity',
                    'status',
                    'unit_price',
                    'final_price',
                    'is_pre_order',
                    'created_at'
                ])->with([
                    'product' => function ($query) {
                        $query->select([
                            'id',
                            'name',
                            'slug',
                            'feature_image',
                            'type'
                        ]);
                    },
                    'productVariation' => function ($query) {
                        $query->withTrashed()->select([
                            'id',
                            'product_id',
                            'price',
                            'image_path'
                        ])->with(['attributeValues' => function ($q) {
                            $q->withTrashed()->with(['attribute' => function ($a) {
                                $a->withTrashed();
                            }]);
                        }]);
                    }
                ]);
            }
        ])->paginate($perPage)
            ->through(function ($order) {
                return $this->formatOrder($order);
            });
    });
}

    protected function formatOrder($order): array
    {
        Log::info('Formatting order mamun', ['order' => $order]);

        $duplicatePercentage = $this->calculateDuplicatePercentage($order);

        return [
            'id' => $order->id,
            'duplicate_percentage' => $duplicatePercentage,
            'order_number' => $order->order_number,
            'source' => $order->source ?? 'website',
            'status' => $order->status,
            'courier_name' => $order->courier_name ?? 'N/A',
            'tracking_number' => $order->tracking_number ?? 'N/A',
            'consignment_id' => $order->consignment_id ?? 'N/A',
            'delivery_status' => $order->delivery_status ?? 'N/A',
            'customer_notes' => $order->customer_notes ?? '',
            'admin_notes' => $order->admin_notes ?? '',
            'city_id' => $order->city_id,
            'shipping_cost' => $order->shipping_cost ?? 0.00,
            'zone_id' => $order->zone_id,
            'subtotal' => $order->subtotal ?? 0.00,
            'discount_total' => $order->discount_total ?? 0.00,
            'area_id' => $order->area_id,
            'city_name' =>  $order->city_name  ?? 'N/A',
            'area_name' => $order->area_name ?? 'N/A',
            'zone_name' => $order->zone_name ?? 'N/A',
            'is_courier' => $order->is_courier ?? false,
            'is_cod' => $order->payment_method === 'cod' ? true : false,
            'is_paid' => $order->payment_status === 'paid' ? true : false,
            'customer' => [
                'name' => $order->customer_name ?? 'N/A',
                'email' => $order->customer_email ?? 'N/A',
                'phone' => $order->customer_phone ?? 'N/A',
                'address' => $order->shipping_address ?? 'N/A',
                'note' => $order->customer_notes ?? 'N/A'
            ],
            'total' => $order->total ?? 0.00,
            'paid_amount' => $order->paid_amount ?? 0.00,
            'return_charge' => $order->return_charge ?? 0.00,
            'payment_status' => $order->payment_status ?? 'unpaid',
            'payment_method' => $order->payment_method ?? 'N/A',
            'transaction_id' => $order->transaction_id ?? 'N/A',
            'shipping_method' => $order->shipping_method ?? 'N/A',
            'date' => $order->created_at->toIso8601String(),
            'updated_at' => $order->updated_at->toIso8601String(),
            'items' => $order->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'returned_quantity' => $item->returned_quantity ?? 0,
                    'status' => $item->status ?? 'pending',
                    'price' => $item->final_price,
                    'is_pre_order' => $item->is_pre_order,
                    'product' => $this->formatProduct($item->product),
                    'variation' => $item->product_variation_id && $item->productVariation ? [
                        'id' => $item->productVariation->id,
                        'is_deleted' => $item->productVariation->trashed(),
                        'attributes' => optional($item->productVariation->attributeValues)
                            ->filter(function ($value) {
                                return $value && $value->attribute;
                            })
                            ->mapWithKeys(function ($value) {
                                return [$value->attribute->name => $value->value];
                            }) ?? collect(),
                        'image' => $item->productVariation->image_path ?? 'N/A'
                    ] : null
                ];
            })->toArray()
        ];
    }

    protected function formatProduct($product): array
    {
        if (!$product) {
            return [
                'id' => null,
                'name' => 'Product Deleted',
                'slug' => null,
                'image' => null,
                'type' => null
            ];
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'image' => $product->feature_image,
            'type' => $product->type
        ];
    }

    /**
     * Calculate duplicate percentage for an order
     *
     * @param mixed $order
     * @return int
     */
    protected function calculateDuplicatePercentage($order): int
    {
        $duplicatePercentage = 0;
        if (!empty($order->customer_phone) && $order->items && $order->items->isNotEmpty()) {
            $previousOrder = \App\Models\Order::select('orders.id', 'orders.created_at')
                ->join('order_items', 'orders.id', '=', 'order_items.order_id')
                ->where('orders.customer_phone', $order->customer_phone)
                ->where('orders.id', '!=', $order->id)
                ->where('orders.created_at', '<=', $order->created_at)
                ->where(function ($query) use ($order) {
                    foreach ($order->items as $item) {
                        $query->orWhere(function ($q) use ($item) {
                            $q->where('order_items.product_id', $item->product_id);
                            if ($item->product_variation_id) {
                                $q->where('order_items.product_variation_id', $item->product_variation_id);
                            } else {
                                $q->whereNull('order_items.product_variation_id');
                            }
                        });
                    }
                })
                ->orderBy('orders.created_at', 'desc')
                ->first();

            if ($previousOrder) {
                $daysDifference = \Carbon\Carbon::parse($order->created_at)->startOfDay()->diffInDays(\Carbon\Carbon::parse($previousOrder->created_at)->startOfDay());
                if ($daysDifference == 0) {
                    $duplicatePercentage = 100;
                } elseif ($daysDifference == 1) {
                    $duplicatePercentage = 80;
                } elseif ($daysDifference == 2) {
                    $duplicatePercentage = 50;
                } elseif ($daysDifference <= 3) {
                    $duplicatePercentage = 30;
                }
            }
        }

        return $duplicatePercentage;
    }

    /**
     * Build base order query with all filters and sorting
     *
     * @param array $filters
     * @param string $sortBy
     * @param string $sortDirection
     * @return Builder
     */
    private function buildOrderQuery(
        array $filters = [],
        string $sortBy = 'created_at',
        string $sortDirection = 'desc'
    ): Builder {
        $query = Order::query()
            ->select([
                'orders.id',
                'orders.order_number',
                'orders.consignment_id',
                'orders.user_id',
                'orders.session_id',
                'orders.status',
                'orders.customer_email',
                'orders.delivery_status',
                'orders.customer_phone',
                'orders.customer_name',
                'orders.shipping_address',
                'orders.area',
                'orders.shipping_cost',
                'orders.subtotal',
                'orders.discount_total',
                'orders.total',
                'orders.paid_amount',
                'orders.return_charge',
                'orders.payment_status',
                'orders.payment_method',
                'orders.transaction_id',
                'orders.shipping_method',
                'orders.tracking_number',
                'orders.customer_notes',
                'orders.admin_notes',
                'orders.city_id',
                'orders.zone_id',
                'orders.area_id',
                'orders.courier_name',
                'orders.city_name',
                'orders.zone_name',
                'orders.area_name',
                'orders.is_courier',
                'orders.source',
                'orders.created_at',
                'orders.updated_at'
            ]);

        // Apply filters
        $this->applyFilters($query, $filters);

        // Apply sorting
        $this->applySorting($query, $sortBy, $sortDirection);

        return $query;
    }


    /**
     * Apply filters to the query builder
     *
     * @param Builder $query
     * @param array $filters
     * @return void
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        Log::info('Applying filters', $filters);

        // Exclude incomplete orders from main order list unless explicitly filtering for them
        if (!isset($filters['status']) || $filters['status'] !== 'incomplete') {
            $query->where('status', '!=', 'incomplete');
        }

        // Filter by status
        if (!empty($filters['status'])) {
            if (is_array($filters['status'])) {
                $query->whereIn('status', $filters['status']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Filter by payment status
        if (!empty($filters['payment_status'])) {
            if (is_array($filters['payment_status'])) {
                $query->whereIn('payment_status', $filters['payment_status']);
            } else {
                $query->where('payment_status', $filters['payment_status']);
            }
        }
        
        // Filter by delivery status
        if (!empty($filters['delivery_status'])) {
            if (is_array($filters['delivery_status'])) {
                $query->whereIn('delivery_status', $filters['delivery_status']);
            } else {
                $query->where('delivery_status', $filters['delivery_status']);
            }
        }

        // Filter by source
        if (!empty($filters['source'])) {
            if (is_array($filters['source'])) {
                $query->whereIn('source', $filters['source']);
            } else {
                $query->where('source', $filters['source']);
            }
        }

        // Date range filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Customer search (email or name)
        if (!empty($filters['customer_search'])) {
            $search = $filters['customer_search'];
            $query->where(function ($q) use ($search) {
                $q->where('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('order_number', 'like', "%{$search}%");
            });
        }

        // Order number search
        // if (!empty($filters['order_number'])) {
        //     $query->where('order_number', 'like', "%{$filters['order_number']}%");
        // }

        // Total amount range
        if (!empty($filters['min_total'])) {
            $query->where('total', '>=', $filters['min_total']);
        }

        if (!empty($filters['max_total'])) {
            $query->where('total', '<=', $filters['max_total']);
        }
    }

    /**
     * Apply sorting to the query builder
     *
     * @param Builder $query
     * @param string $sortBy
     * @param string $sortDirection
     * @return void
     */
    private function applySorting(Builder $query, string $sortBy, string $sortDirection): void
    {
        // Validate sort column
        $validSortColumns = [
            'id',
            'order_number',
            'status',
            'customer_email',
            'customer_name',
            'total',
            'created_at',
            'payment_status'
        ];

        // Default to created_at if invalid column
        if (!in_array($sortBy, $validSortColumns)) {
            $sortBy = 'created_at';
        }

        // Ensure valid sort direction
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * Get order statistics for dashboard
     *
     * @param int $days Number of days for recent orders
     * @return array
     */
    public function getOrderStatistics(int $days = 30): array
    {
        $cacheKey = "order_stats:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($days) {
            // Get date for filtering
            $startDate = now()->subDays($days)->startOfDay();

            // Get total stats
            $totalStats = Order::select([
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(total) as total_revenue'),
                DB::raw('AVG(total) as average_order_value')
            ])->first();

            // Get stats by status
            $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            // Get recent orders trend
            $recentOrdersTrend = Order::select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as orders'),
                DB::raw('SUM(total) as revenue')
            ])
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->toArray();

            return [
                'total_orders' => $totalStats->total_orders ?? 0,
                'total_revenue' => $totalStats->total_revenue ?? 0,
                'average_order_value' => $totalStats->average_order_value ?? 0,
                'orders_by_status' => $ordersByStatus,
                'recent_orders_trend' => $recentOrdersTrend
            ];
        });
    }

    /**
     * Get product variation details with attributes
     *
     * @param int $variationId
     * @return array
     */
    public function getProductVariationDetails(int $variationId): array
    {
        $cacheKey = "product_variation:{$variationId}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($variationId) {
            // Query to get variation details with formatted attributes
            $variation = DB::table('product_variations as pv')
                ->select([
                    'pv.id',
                    'pv.product_id',
                    'pv.sku',
                    'pv.price',
                    'pv.stock_quantity',
                    'p.name as product_name'
                ])
                ->join('products as p', 'p.id', '=', 'pv.product_id')
                ->where('pv.id', $variationId)
                ->first();

            if (!$variation) {
                return [];
            }

            // Get attributes
            $attributes = DB::table('variation_attributes as va')
                ->select([
                    'a.id as attribute_id',
                    'a.name as attribute_name',
                    'av.value'
                ])
                ->join('attribute_values as av', 'av.id', '=', 'va.attribute_value_id')
                ->join('attributes as a', 'a.id', '=', 'av.attribute_id')
                ->where('va.product_variation_id', $variationId)
                ->get()
                ->toArray();

            // Format response
            $result = (array)$variation;
            $result['attributes'] = [];

            foreach ($attributes as $attribute) {
                $result['attributes'][] = [
                    'name' => $attribute->attribute_name,
                    'value' => $attribute->value
                ];
            }

            return $result;
        });
    }
}
