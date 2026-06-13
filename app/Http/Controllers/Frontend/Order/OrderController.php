<?php

namespace App\Http\Controllers\Frontend\Order;

use App\Http\Controllers\Controller;
use App\Services\Order\OrderInterface;
use App\Services\Cart\CartService;
use App\Services\MetaConversion\MetaConversionService;
use App\Http\Resources\Cart\CartResource;
use App\Models\DeliveryZone;
use App\Models\GeneralSetting;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    protected $orderService;
    protected $cartService;
    protected $metaService;

    public function __construct(
        OrderInterface $orderService,
        CartService $cartService,
        MetaConversionService $metaService
    ) {
        $this->orderService = $orderService;
        $this->cartService  = $cartService;
        $this->metaService  = $metaService;
    }

    /**
     * Show the Checkout Page
     */
    public function create(Request $request)
    {
        // 1. Retrieve current cart items to display on checkout
        $userId = auth()->id();
        $sessionId = $request->cookie('cart_session_id') ?? $request->session()->get('cart_session_id');
        
        $cart = $this->cartService->getCart($userId, $sessionId);

        // If cart is empty, redirect back to cart or shop
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        return Inertia::render('Frontend/Order/Checkout', [
            'cart' => new CartResource($cart),
            'user' => $request->user(),
        ]);
    }

    /**
     * Store the Order (Handles both complete and incomplete orders)
     */
    public function store(Request $request)
    {
        $isIncomplete = $request->input('status') === 'incomplete';

        // Conditional validation: Relaxed for incomplete orders
        if (!$isIncomplete) {
            $validated = $request->validate([
                'customer_name' => 'required|string',
                'customer_phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
                'shipping_address' => 'required|string',
                'shipping_district' => 'required|string',
                'shipping_thana' => 'required|string',
                'payment_method' => 'required|string',
                'payment_status' => 'required|in:unpaid,paid,refunded',
                'transaction_id' => 'required_unless:payment_method,cod|nullable|string|max:255',
                'items' => 'required|array|min:1',
            ]);

            $request->merge([
                'shipping_cost' => $this->resolveDeliveryCharge(
                    $validated['shipping_district'],
                    $validated['shipping_thana']
                ),
            ]);
        } else {
            $request->validate([
                'customer_phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
                'customer_name' => 'required|string|max:255',
                'shipping_address' => 'required|string|max:500',
                'items' => 'required|array|min:1',
            ]);
        }

        try {
            // Reuse OrderService to create the order
            $order = $this->orderService->createOrder($request->all());

            // For incomplete orders, return JSON (used by background axios call)
            if ($isIncomplete) {
                return response()->json([
                    'message' => 'Incomplete order tracked',
                    'order_number' => $order->order_number
                ], 201);
            }

            $this->storeOrderAccess($request, $order->order_number);

            // Clear Cart after successful complete order
            $userId = auth()->id();
            $sessionId = $request->cookie('cart_session_id') ?? $request->session()->get('cart_session_id');
            $this->cartService->clearCart($userId, $sessionId);
            
            // Clear applied coupon from session
            session()->forget('applied_coupon');

            // Redirect to Success Page
            return redirect()->route('order.success', ['order' => $order->order_number]);

        } catch (\Exception $e) {
            Log::error('Order Creation Failed: ' . $e->getMessage());
            
            if ($isIncomplete) {
                return response()->json(['error' => $e->getMessage()], 422);
            }
            
            return redirect()->back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /**
     * Show Success Page with full order data
     */
    public function success($orderNumber)
    {
        // Fetch full order data with relationships for the view
        $orderData = Order::where('order_number', $orderNumber)
            ->with([
                'items',
                'items.product',
                'items.productVariation',
                'items.productVariation.attributes.value.attribute',
                'items.productVariation.attributes.value'
            ])
            ->firstOrFail();

        if (!$this->canAccessOrder(request(), $orderData)) {
            return redirect()
                ->route('order.track')
                ->with('error', 'You are not allowed to view that order.');
        }

        // Generate the same event_id the CAPI job will use (for Pixel deduplication)
        $metaEventId = $this->metaService->generateEventId(
            $orderData->order_number,
            'Purchase'
        );

        // Browser-side pixel Purchase should only fire when mode is on_order_created.
        // For on_confirmed / on_delivered, CAPI fires later (server-side only).
        $settings = \App\Models\TrackingSettings::getSettings();
        $firePixelNow = $settings->pixel_enabled
            && in_array($settings->purchase_fire_mode, ['on_order_created']);

        return Inertia::render('Frontend/Order/Success', [
            'order'          => $orderData,
            'meta_event_id'  => $metaEventId,
            'fire_pixel_now' => $firePixelNow,
        ]);
    }
    /**
     * Track Order Page & Logic
     */
    public function trackOrder(Request $request)
    {
        $orders = [];
        $searched = false;

        if ($request->filled('track_input')) {
            $searched = true;
            $input = trim((string) $request->input('track_input'));

            $query = Order::query()->with([
                'items',
                'items.product',
                'items.productVariation',
                'items.productVariation.attributes.value.attribute',
                'items.productVariation.attributes.value'
            ]);

            // Try to match standard Bangladeshi phone number patterns
            // In database, customer_phone is stored as "01712345678" (11 digits starting with 01)
            $digits = preg_replace('/\D/', '', $input);

            if (preg_match('/^(?:\+?88)?(01[3-9]\d{8})$/', $input, $matches)) {
                $phoneNumber = $matches[1];
                $query->where('customer_phone', $phoneNumber);
            } elseif (ctype_digit($digits) && strlen($digits) >= 10 && strlen($digits) <= 15) {
                // Normalize and search by phone number fallback
                $normalizedPhone = '0' . substr($digits, -10);
                $query->where('customer_phone', $normalizedPhone);
            } else {
                // Otherwise, treat as Order ID / Order Number
                $upperInput = strtoupper($input);
                if (Str::startsWith($upperInput, 'TNM-')) {
                    $query->where('order_number', $upperInput);
                } else {
                    $query->where(function($q) use ($upperInput) {
                        $q->where('order_number', $upperInput)
                          ->orWhere('order_number', 'TNM-' . $upperInput);
                    });
                }
            }

            $orders = $query->orderBy('created_at', 'desc')->get();
        }

        return Inertia::render('Frontend/Order/TrackOrder', [
            'orders' => $orders,
            'filters' => $request->only(['track_input']),
            'searched' => $searched
        ]);
    }
    public function getDeliveryCharge(Request $request)
    {
        $district = $request->query('district');
        $thana = $request->query('thana');

        if (!$district) {
            return response()->json(['charge' => 0]);
        }

        return response()->json([
            'charge' => $this->resolveDeliveryCharge($district, $thana),
        ]);
    }

    private function canAccessOrder(Request $request, Order $order): bool
    {
        if ($request->user() && (int) $request->user()->id === (int) $order->user_id) {
            return true;
        }

        $allowedOrders = $request->session()->get('order_access_numbers', []);

        return in_array($order->order_number, $allowedOrders, true);
    }

    private function storeOrderAccess(Request $request, string $orderNumber): void
    {
        $allowedOrders = $request->session()->get('order_access_numbers', []);
        $allowedOrders[] = $orderNumber;
        $request->session()->put('order_access_numbers', array_values(array_unique($allowedOrders)));
    }

    private function resolveDeliveryCharge(string $district, ?string $thana = null): float
    {
        $thanaZone = DeliveryZone::query()
            ->where('type', DeliveryZone::TYPE_THANA)
            ->where('is_active', true)
            ->where('name', $thana)
            ->whereHas('parent', function ($query) use ($district) {
                $query->where('type', DeliveryZone::TYPE_DISTRICT)
                    ->where('name', $district)
                    ->where('is_active', true);
            })
            ->first();

        if ($thanaZone && $thanaZone->charge !== null) {
            return (float) $thanaZone->charge;
        }

        $districtZone = DeliveryZone::query()
            ->where('type', DeliveryZone::TYPE_DISTRICT)
            ->where('name', $district)
            ->where('is_active', true)
            ->first();

        if ($districtZone && $districtZone->charge !== null) {
            return (float) $districtZone->charge;
        }

        $settings = GeneralSetting::first();

        if ($district === 'Dhaka') {
            return (float) ($settings?->shipping_charge_inside_dhaka ?? 60);
        }

        return (float) ($settings?->shipping_charge_outside_dhaka ?? 120);
    }
}
