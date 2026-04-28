<?php

namespace App\Http\Controllers\Admin\BusinessDashboard;

use App\Http\Controllers\Controller;
use App\Services\Report\FinancialReportServiceInterface;
use Illuminate\Http\Request;

class BusinessDashboardController extends Controller
{
    protected $reportService;

    public function __construct(FinancialReportServiceInterface $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index(Request $request)
    {
        $filter = $request->get('filter', 'monthly');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Get overview data
        $overview = $this->reportService->getBusinessOverview($filter, $startDate, $endDate);

        $chartData = $this->reportService->getChartData($filter, $startDate, $endDate);
        $ordersChartData = $this->reportService->getOrdersChartData($filter, $startDate, $endDate);
        $monthlyOverviewData = $this->reportService->getMonthlyOverviewData($filter, $startDate, $endDate);
        $productProfitReport = $this->reportService->getProductProfitReport($filter, $startDate, $endDate);

        return inertia('Admin/BusinessDashboard/Index', [
            'revenue' => $overview['revenue'],
            'cogs' => $overview['cogs'],
            'gross_profit' => $overview['gross_profit'],
            'expenses' => $overview['expenses'],
            'profit' => $overview['profit'],
            'totalOrders' => $overview['total_orders'],
            'filter' => $filter,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'chartData' => $chartData,
            'ordersChartData' => $ordersChartData,
            'monthlyOverviewData' => $monthlyOverviewData,
            'productProfitReport' => $productProfitReport,
        ]);
    }
}
