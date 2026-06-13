<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookEntry;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\Courier\CourierManager;

class FacebookEntryController extends Controller
{
    protected $courierManager;

    public function __construct(CourierManager $courierManager)
    {
        $this->courierManager = $courierManager;
    }

    public function index(Request $request)
    {
        $query = FacebookEntry::orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('tracking_id', 'like', "%{$search}%");
            });
        }

        $history = $query->paginate(20);

        return Inertia::render('Admin/FacebookEntries/Index', [
            'history' => $history
        ]);
    }

    public function history(Request $request)
    {
        $search = $request->input('search');
        $area = $request->input('area');
        $dateFilter = $request->input('date_filter', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = FacebookEntry::orderBy('created_at', 'desc');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('tracking_id', 'like', "%{$search}%");
            });
        }

        if (in_array($area, ['inside_dhaka', 'outside_dhaka'], true)) {
            $query->where('area', $area);
        }

        if ($dateFilter === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($dateFilter === 'this_month') {
            $query->whereYear('created_at', Carbon::now()->year)
                  ->whereMonth('created_at', Carbon::now()->month);
        } elseif ($dateFilter === 'custom' && $startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            if ($start->gt($end)) {
                [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
            }

            $query->whereBetween('created_at', [$start, $end]);
        }

        $history = $query->paginate(30)->withQueryString();

        return Inertia::render('Admin/FacebookEntries/History', [
            'history' => $history,
            'filters' => [
                'search' => $search,
                'area' => $area ?? 'all',
                'date_filter' => $dateFilter,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    private function convertBanglaToEnglishDigits($string)
    {
        if (is_null($string)) {
            return null;
        }
        $string = (string) $string;
        $banglaDigits = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($banglaDigits, $englishDigits, $string);
    }

    public function store(Request $request)
    {
        if ($request->has('customer_phone')) {
            $request->merge([
                'customer_phone' => $this->convertBanglaToEnglishDigits($request->input('customer_phone'))
            ]);
        }
        if ($request->has('bill_amount')) {
            $request->merge([
                'bill_amount' => $this->convertBanglaToEnglishDigits($request->input('bill_amount'))
            ]);
        }
        if ($request->has('shipping_cost')) {
            $request->merge([
                'shipping_cost' => $this->convertBanglaToEnglishDigits($request->input('shipping_cost'))
            ]);
        }

        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_address' => 'nullable|string',
            'bill_amount' => 'required|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'area' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Create Entry
            $entry = FacebookEntry::create($validated);

            // Handle Stock Decrement
            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    $qty = $item['quantity'];
                    
                    if (!empty($item['variation_id'])) {
                        $variation = ProductVariation::find($item['variation_id']);
                        if ($variation) {
                            $variation->decrement('stock', $qty);
                            $variation->increment('sold_stock', $qty);
                        }
                    }

                    $product = Product::find($item['id']);
                    if ($product) {
                        $product->decrement('stock', $qty);
                        $product->increment('sold_stock', $qty);
                    }
                }
            }

            // Steadfast Integration
            try {
                $steadfastOrder = [
                    'invoice' => 'FB-' . $entry->id,
                    'recipient_name' => $entry->customer_name ?? 'Facebook Customer',
                    'recipient_phone' => $entry->customer_phone,
                    'recipient_address' => $entry->customer_address ?? 'Not Provided',
                    'cod_amount' => $entry->bill_amount,
                    'note' => 'Facebook Entry #' . $entry->id,
                ];

                $response = $this->courierManager->driver('steadfast')->createOrder($steadfastOrder);
                
                if (isset($response['status']) && $response['status'] == 200) {
                    $entry->update([
                        'tracking_id' => $response['consignment']['consignment_id'] ?? null,
                        'status' => 'processing'
                    ]);
                }
            } catch (\Exception $e) {
                // Log but don't fail the whole transaction if courier fails
                \Log::error('Steadfast error: ' . $e->getMessage());
            }

            DB::commit();

            return redirect()->back()->with([
                'success' => 'Entry created successfully.',
                'tracking_id' => $entry->tracking_id,
                'cod_amount' => $entry->bill_amount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to save entry: ' . $e->getMessage());
        }
    }

    public function getStats(Request $request)
    {
        $filter = $request->input('period', 'today');
        $query = FacebookEntry::query();

        if ($filter === 'today') {
            $query->whereDate('created_at', Carbon::today());
        } elseif ($filter === 'this_month') {
            $query->whereMonth('created_at', Carbon::now()->month)
                  ->whereYear('created_at', Carbon::now()->year);
        } elseif ($filter === 'last_2_months') {
            $query->where('created_at', '>=', Carbon::now()->subMonths(2));
        } elseif ($filter === 'custom' && $request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        }

        $stats = [
            'total' => (clone $query)->count(),
            'inside_dhaka' => (clone $query)->where('area', 'inside_dhaka')->count(),
            'outside_dhaka' => (clone $query)->where('area', 'outside_dhaka')->count(),
        ];

        return response()->json($stats);
    }

    public function searchProducts(Request $request)
    {
        $search = $request->input('search');
        if (!$search) return response()->json([]);

        $products = Product::where('status', 'Published')
            ->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            })
            ->with(['variations.attributes.value.attribute'])
            ->take(10)
            ->get();

        return response()->json($products);
    }
}
