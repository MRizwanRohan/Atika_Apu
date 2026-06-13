<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Order;
// carbon
use Carbon\Carbon;
use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Summary Metrics
        $salesToday = Order::whereDate('created_at', Carbon::today())
            ->where('status', '!=', 'incomplete')
            ->sum('total');

        $ordersToday = Order::whereDate('created_at', Carbon::today())
            ->where('status', '!=', 'incomplete')
            ->count();

        $preOrdersCount = Order::whereHas('items', function ($query) {
            $query->where('is_pre_order', true);
        })->where('status', '!=', 'cancelled')->count();

        $requestsCount = \App\Models\PreOrderLead::count();

        // Helper function for top selling products
        $getTopSelling = function ($startDate = null) {
            $query = Product::select('products.id', 'products.name', 'products.feature_image', 'products.is_pre_order')
                ->join('order_items', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.status', '!=', 'cancelled')
                ->where('orders.status', '!=', 'incomplete');

            if ($startDate) {
                $query->where('orders.created_at', '>=', $startDate);
            }

            return $query->selectRaw('SUM(order_items.quantity) as total_sold')
                ->groupBy('products.id', 'products.name', 'products.feature_image', 'products.is_pre_order')
                ->orderByDesc('total_sold')
                ->take(5)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'feature_image_url' => $product->feature_image_url,
                        'total_sold' => (int) $product->total_sold,
                        'is_pre_order' => (bool) $product->is_pre_order,
                    ];
                });
        };

        $topSelling = [
            'today' => $getTopSelling(Carbon::today()),
            'week' => $getTopSelling(Carbon::now()->startOfWeek()),
            'month' => $getTopSelling(Carbon::now()->startOfMonth()),
            'year' => $getTopSelling(Carbon::now()->startOfYear()),
            'overall' => $getTopSelling(null),
        ];

        // Low Stock Alerts
        $lowStockProducts = Product::where('stock', '<', 10)
            ->where('status', 'Published')
            ->orderBy('stock', 'asc')
            ->take(5)
            ->get();

        // Chart + Performance Data
        $dailyOrdersData = $this->getDailyOrdersData();
        $orderStatusData = $this->getOrderStatusData();
        $paymentStatusData = $this->getPaymentStatusData();

        // Recent Orders
        $recentOrders = Order::with([
            'customer',
            'items.product',
        ])
            ->latest()
            ->take(10)
            ->get();


        $data = [
            'summary' => [
                'salesToday' => [
                    'title' => 'Sales Today',
                    'value' => $salesToday,
                    'count' => null,
                ],
                'ordersToday' => [
                    'title' => 'Orders',
                    'value' => null,
                    'count' => $ordersToday,
                ],
                'preOrders' => [
                    'title' => 'Pre-Orders',
                    'value' => null,
                    'count' => $preOrdersCount,
                ],
                'requests' => [
                    'title' => 'Requests',
                    'value' => null,
                    'count' => $requestsCount,
                ],
            ],
            'topSelling' => $topSelling,
            'lowStock' => $lowStockProducts,
            'dailyOrdersData' => $dailyOrdersData,
            'monthlyOrdersData' => $this->getMonthlyOrdersData(),
            'orderStatusData' => $orderStatusData,
            'paymentStatusData' => $paymentStatusData,
            'deliverySalesData' => $this->getDeliverySalesData(),
            'recentOrders' => $recentOrders,
        ];

        return Inertia::render('Admin/Dashboard/Index', [
            'data' => $data,
        ]);


    }

    private function getDailyOrdersData()
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $orders = Order::selectRaw('DATE(created_at) as date, COUNT(*) as order_count, SUM(total) as revenue')
            ->where('status', '!=', 'incomplete')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->date)->format('m/d'));

        $result = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $formattedDate = $currentDate->format('m/d');
            $day = $orders->get($formattedDate);

            $result[] = [
                'date' => $formattedDate,
                'orders' => $day->order_count ?? 0,
                'revenue' => $day->revenue ?? 0,
            ];

            $currentDate->addDay();
        }

        return $result;
    }

    private function getMonthlyOrdersData()
    {
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $orders = Order::selectRaw('DATE(created_at) as date, COUNT(*) as order_count, SUM(total) as revenue')
            ->where('status', '!=', 'incomplete')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->date)->format('m/d'));

        $result = [];

        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $formattedDate = $currentDate->format('m/d');
            $day = $orders->get($formattedDate);

            $result[] = [
                'date' => $formattedDate,
                'orders' => $day->order_count ?? 0,
                'revenue' => $day->revenue ?? 0,
            ];

            $currentDate->addDay();
        }

        return $result;
    }

    private function getProductPerformanceData()
    {
        $topProducts = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select('products.id', 'products.name', DB::raw('SUM(order_items.quantity) as sales'))
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('sales')
            ->take(5)
            ->get();

        $totalSales = $topProducts->sum('sales');

        return $topProducts->map(function ($item) use ($totalSales) {
            return [
                'name' => $item->name,
                'sales' => $item->sales,
                'percentage' => $totalSales > 0 ? round(($item->sales / $totalSales) * 100, 1) : 0,
            ];
        });
    }

    private function getOrderStatusData()
    {
        return Order::select('status', DB::raw('COUNT(*) as value'))
            ->where('status', '!=', 'incomplete')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->status),
                    'value' => $item->value,
                ];
            });
    }

    private function getPaymentStatusData()
    {
        return Order::select('payment_status', DB::raw('COUNT(*) as value'))
            ->where('status', '!=', 'incomplete')
            ->groupBy('payment_status')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->payment_status ?? 'Unpaid'),
                    'value' => $item->value,
                ];
            });
    }

    private function percentChange($current, $previous)
    {
        // Avoid NaN: if previous is 0, return 0 when both zero, or 100 when previous is 0 and current > 0
        if ((float) $previous === 0.0) {
            return ((float) $current === 0.0) ? 0 : 100;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getDeliverySalesData()
    {
        // Return counts and total sales grouped by delivery status
        $rows = Order::select('status', DB::raw('COUNT(*) as orders'), DB::raw('SUM(total) as sales'))
            ->where('status', '!=', 'incomplete')
            ->groupBy('status')
            ->get();

        return $rows->map(function ($item) {
            return [
                'name' => ucfirst($item->status),
                'orders' => (int) $item->orders,
                'sales' => (float) $item->sales,
            ];
        });
    }



}
