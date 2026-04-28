<?php

namespace App\Services\Report;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\EmployeeSalary;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialReportService implements FinancialReportServiceInterface
{
    public function getBusinessOverview(string $filter, ?string $startDate = null, ?string $endDate = null): array
    {
        $boundaries = $this->getDateRangeBoundaries($filter, $startDate, $endDate);
        
        $dateRange = function($query) use ($boundaries) {
            $query->whereBetween('created_at', [$boundaries[0], $boundaries[1]]);
        };

        // 1. Revenue (Sum of paid/partially paid orders)
        $revenue = Order::where(function($q) {
                $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
            })
            ->where($dateRange)
            ->sum('total');

        // 2. COGS (Cost of Goods Sold)
        $cogs = OrderItem::whereHas('order', function($q) use ($dateRange) {
                $q->where(function($qq) {
                    $qq->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
                })->where($dateRange);
            })
            ->selectRaw('SUM(unit_cost * (quantity - IFNULL(returned_quantity, 0))) as total_cogs')
            ->value('total_cogs') ?? 0;

        // 3. Operational Expenses
        $generalExpenses = Expense::where($dateRange)->sum('amount');
        
        // Salaries use paid_date
        $salaries = EmployeeSalary::whereBetween('paid_date', [$boundaries[0], $boundaries[1]])
            ->sum('amount');

        // 4. Hidden Costs (Shipping loss, Packaging, Gateway fees)
        $hiddenCosts = Order::where(function($q) {
                $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
            })
            ->where($dateRange)
            ->selectRaw('SUM(actual_courier_fee + gateway_fee + packaging_cost) as total_hidden_costs, SUM(return_charge) as total_return_charges')
            ->first();

        $totalOperationalExpenses = $generalExpenses + $salaries + (float)($hiddenCosts->total_hidden_costs ?? 0);
        $totalReturnRevenue = (float)($hiddenCosts->total_return_charges ?? 0);

        // 5. Profits
        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $totalOperationalExpenses + $totalReturnRevenue;

        $totalOrdersCount = Order::where(function($q) {
                $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
            })
            ->where($dateRange)
            ->count();

        return [
            'revenue' => (float)$revenue,
            'cogs' => (float)$cogs,
            'gross_profit' => (float)$grossProfit,
            'expenses' => (float)$totalOperationalExpenses,
            'profit' => (float)$netProfit,
            'total_orders' => $totalOrdersCount,
        ];
    }

    public function getChartData(string $filter, ?string $startDate = null, ?string $endDate = null): array
    {
        $boundaries = $this->getDateRangeBoundaries($filter, $startDate, $endDate);
        $start = $boundaries[0];
        $end = $boundaries[1];

        $revenueData = [];
        $expenseData = [];
        $categories = [];

        // Determine step (daily or monthly)
        $isDaily = $filter !== 'yearly' && $start->diffInDays($end) <= 60;

        if ($isDaily) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $categories[] = $date->format($filter === 'weekly' ? 'D' : 'M d');
                
                $revenue = Order::where(function($q) {
                        $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
                    })
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->sum('total');
                
                $expenses = Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount') +
                           Purchase::whereDate('created_at', $date->format('Y-m-d'))->sum('total_amount');
                
                $revenueData[] = (float)$revenue;
                $expenseData[] = (float)$expenses;
            }
        } else {
            for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
                $categories[] = $date->format('M Y');
                
                $revenue = Order::where(function($q) {
                        $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
                    })
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total');
                
                $expenses = Expense::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount') +
                    Purchase::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total_amount');
                
                $revenueData[] = (float)$revenue;
                $expenseData[] = (float)$expenses;
            }
        }

        return [
            'categories' => $categories,
            'revenue' => $revenueData,
            'expenses' => $expenseData,
        ];
    }

    public function getOrdersChartData(string $filter, ?string $startDate = null, ?string $endDate = null): array
    {
        $boundaries = $this->getDateRangeBoundaries($filter, $startDate, $endDate);
        $start = $boundaries[0];
        $end = $boundaries[1];

        $ordersData = [];
        $categories = [];

        $isDaily = $filter !== 'yearly' && $start->diffInDays($end) <= 60;

        if ($isDaily) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $categories[] = $date->format($filter === 'weekly' ? 'D' : 'M d');
                $count = Order::where(function($q) {
                        $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
                    })
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->count();
                $ordersData[] = $count;
            }
        } else {
            for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
                $categories[] = $date->format('M Y');
                $count = Order::where(function($q) {
                        $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
                    })
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
                $ordersData[] = $count;
            }
        }

        return [
            'categories' => $categories,
            'orders' => $ordersData,
        ];
    }

    public function getMonthlyOverviewData(string $filter, ?string $startDate = null, ?string $endDate = null): array
    {
        $boundaries = $this->getDateRangeBoundaries($filter, $startDate, $endDate);
        $start = $boundaries[0];
        $end = $boundaries[1];

        $salaryData = [];
        $expenseData = [];
        $purchaseData = [];
        $revenueData = [];
        $categories = [];

        $isDaily = $filter !== 'yearly' && $start->diffInDays($end) <= 60;

        if ($isDaily) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
                $categories[] = $date->format($filter === 'weekly' ? 'D' : 'M d');
                
                $salaryData[] = (float)EmployeeSalary::whereDate('paid_date', $date->format('Y-m-d'))->sum('amount');
                $expenseData[] = (float)Expense::whereDate('created_at', $date->format('Y-m-d'))->sum('amount');
                $purchaseData[] = (float)Purchase::whereDate('created_at', $date->format('Y-m-d'))->sum('total_amount');
                $revenueData[] = (float)Order::where(function($q) {
                        $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
                    })
                    ->whereDate('created_at', $date->format('Y-m-d'))
                    ->sum('total');
            }
        } else {
            for ($date = $start->copy(); $date->lte($end); $date->addMonth()) {
                $categories[] = $date->format('M Y');
                
                $salaryData[] = (float)EmployeeSalary::whereYear('paid_date', $date->year)->whereMonth('paid_date', $date->month)->sum('amount');
                $expenseData[] = (float)Expense::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('amount');
                $purchaseData[] = (float)Purchase::whereYear('created_at', $date->year)->whereMonth('created_at', $date->month)->sum('total_amount');
                $revenueData[] = (float)Order::where(function($q) {
                        $q->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
                    })
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('total');
            }
        }

        return [
            'categories' => $categories,
            'salary' => $salaryData,
            'expenses' => $expenseData,
            'purchases' => $purchaseData,
            'revenue' => $revenueData,
        ];
    }

    public function getProductProfitReport(string $filter, ?string $startDate = null, ?string $endDate = null): array
    {
        $boundaries = $this->getDateRangeBoundaries($filter, $startDate, $endDate);
        
        return OrderItem::join('products', 'order_items.product_id', '=', 'products.id')
            ->whereHas('order', function($q) use ($boundaries) {
                $q->where(function($qq) {
                    $qq->where('payment_status', 'paid')->orWhere('payment_status', 'partially_paid');
                })->whereBetween('orders.created_at', [$boundaries[0], $boundaries[1]]);
            })
            ->select('order_items.product_id', 'order_items.product_variation_id')
            ->selectRaw('products.name as product_name')
            ->selectRaw('SUM(order_items.quantity - IFNULL(order_items.returned_quantity, 0)) as sold_quantity')
            ->selectRaw('SUM(order_items.final_price) as revenue')
            ->selectRaw('SUM(order_items.unit_cost * (order_items.quantity - IFNULL(order_items.returned_quantity, 0))) as cost')
            ->selectRaw('SUM(order_items.final_price - (order_items.unit_cost * (order_items.quantity - IFNULL(order_items.returned_quantity, 0)))) as profit')
            ->groupBy('order_items.product_id', 'order_items.product_variation_id', 'products.name')
            ->orderBy('profit', 'desc')
            ->limit(20)
            ->get()
            ->toArray();
    }

    private function getDateRangeBoundaries(string $filter, ?string $startDate = null, ?string $endDate = null): array
    {
        switch ($filter) {
            case 'weekly':
                return [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()];
            case 'monthly':
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            case 'yearly':
                return [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
            case 'custom':
                if ($startDate && $endDate) {
                    return [Carbon::parse($startDate)->startOfDay(), Carbon::parse($endDate)->endOfDay()];
                }
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
            default:
                return [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
        }
    }
    public function getSupplierLedgerReport(): array
    {
        return \App\Models\Supplier::select(
                'suppliers.id',
                'suppliers.name',
                'suppliers.opening_balance',
                DB::raw('COALESCE((SELECT SUM(total_amount) FROM purchases WHERE supplier_id = suppliers.id), 0) as total_bill'),
                DB::raw('COALESCE((SELECT SUM(paid_amount) FROM purchases WHERE supplier_id = suppliers.id), 0) + COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_id = suppliers.id), 0) as total_paid'),
                DB::raw('suppliers.opening_balance + 
                         COALESCE((SELECT SUM(total_amount) FROM purchases WHERE supplier_id = suppliers.id), 0) - 
                         COALESCE((SELECT SUM(paid_amount) FROM purchases WHERE supplier_id = suppliers.id), 0) - 
                         COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_id = suppliers.id), 0) as total_due')
            )
            ->groupBy('suppliers.id', 'suppliers.name', 'suppliers.opening_balance')
            ->orderBy('total_due', 'desc')
            ->get()
            ->toArray();
    }
}
