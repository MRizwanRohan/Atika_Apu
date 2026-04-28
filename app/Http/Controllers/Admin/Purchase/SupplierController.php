<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Services\Supplier\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SupplierController extends Controller
{
    protected $service;
    protected $reportService;

    public function __construct(
        SupplierService $service,
        \App\Services\Report\FinancialReportServiceInterface $reportService
    ) {
        $this->service = $service;
        $this->reportService = $reportService;
    }

    public function ledgerReport()
    {
        return inertia('Admin/Purchase/Supplier/LedgerReport', [
            'report' => $this->reportService->getSupplierLedgerReport()
        ]);
    }

    public function recordPayment(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
        ]);

        \App\Models\SupplierPayment::create($request->all());

        return redirect()->back()->with('success', 'Payment recorded successfully');
    }

    public function index(Request $request)
    {
        return inertia('Admin/Purchase/Supplier/Index', [
            'data' => $this->service->getAll($request->search),
            'filters' => $request->only(['search'])
        ]);
    }

    public function show($id)
    {
        $supplier = \App\Models\Supplier::with([
            'purchases' => fn($q) => $q->latest(),
            'payments' => fn($q) => $q->latest()
        ])->findOrFail($id);

        // Calculate summary for the specific supplier
        $totalBill = $supplier->purchases->sum('total_amount');
        $totalPaid = $supplier->purchases->sum('paid_amount') + $supplier->payments->sum('amount');
        $due = $supplier->opening_balance + $totalBill - $totalPaid;

        return inertia('Admin/Purchase/Supplier/Show', [
            'supplier' => $supplier,
            'summary' => [
                'total_bill' => $totalBill,
                'total_paid' => $totalPaid,
                'due' => $due
            ]
        ]);
    }

    public function store(Request $request)
    {
        $this->service->create($request->all());
        return redirect()->back()->with('success', 'Supplier created');
    }

    public function update(Request $request, $id)
    {
        $this->service->update($id, $request->all());
        return redirect()->back()->with('success', 'Supplier updated');
    }

    public function destroy($id)
    {
        \App\Models\Supplier::destroy($id);
        return redirect()->back()->with('success', 'Supplier deleted');
    }
}
