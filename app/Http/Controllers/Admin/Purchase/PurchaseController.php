<?php

namespace App\Http\Controllers\Admin\Purchase;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Purchase\StorePurchaseRequest;
use App\Services\Purchase\PurchaseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ClearsProductCache;

class PurchaseController extends Controller
{
    use ClearsProductCache;

    protected $purchaseService;

    public function __construct(PurchaseServiceInterface $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        $search = $request->search;
        $purchases = $this->purchaseService->getAllPurchases($search);
        return inertia('Admin/Purchase/Purchase/Index', [
            'purchases' => $purchases,
            'filters' => [
                'search' => $search
            ]
        ]);
    }

    public function create()
    {
        return inertia('Admin/Purchase/Purchase/Create', [
            'suppliers' => \App\Models\Supplier::where('is_active', true)->get(),
            'products' => \App\Models\Product::with(['variations.variationAttributes.value'])->get()
        ]);
    }

    public function show($id)
    {
        $purchase = $this->purchaseService->getPurchaseById($id);
        return inertia('Admin/Purchase/Purchase/Show', [
            'purchase' => $purchase
        ]);
    }

    public function store(StorePurchaseRequest $request)
    {
        try {
            $purchase = $this->purchaseService->createPurchase($request->validated());

            // If the purchase was auto-received, clear cache
            if ($purchase && $purchase->status === 'received') {
                $this->clearAllProductCaches();
            }

            return redirect()->route('admin.purchases.index')->with('success', 'Purchase created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function markAsReceived($id)
    {
        try {
            $this->purchaseService->receivePurchase($id);

            // Clear all product caches so newest stock/cost is visible
            $this->clearAllProductCaches();

            return redirect()->back()->with('success', 'Purchase marked as received. Stock and costs updated.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
