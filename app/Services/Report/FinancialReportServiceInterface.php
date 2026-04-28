<?php

namespace App\Services\Report;

interface FinancialReportServiceInterface
{
    public function getBusinessOverview(string $filter, ?string $startDate = null, ?string $endDate = null): array;
    
    public function getChartData(string $filter, ?string $startDate = null, ?string $endDate = null): array;
    
    public function getOrdersChartData(string $filter, ?string $startDate = null, ?string $endDate = null): array;
    
    public function getMonthlyOverviewData(string $filter, ?string $startDate = null, ?string $endDate = null): array;

    public function getProductProfitReport(string $filter, ?string $startDate = null, ?string $endDate = null): array;

    public function getSupplierLedgerReport(): array;
}
