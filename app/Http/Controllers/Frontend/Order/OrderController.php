<?php

namespace App\Http\Controllers\Frontend\Order;

use App\Http\Controllers\Controller;
use App\Services\Order\OrderInterface;
use App\Services\Cart\CartService;
use App\Services\MetaConversion\MetaConversionService;
use App\Http\Resources\Cart\CartResource;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

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
            $request->validate([
                'customer_name' => 'required|string',
                'customer_phone' => 'required|string',
                'shipping_address' => 'required|string',
                'payment_method' => 'required|string',
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
        $orderData = \App\Models\Order::where('order_number', $orderNumber)
            ->with([
                'items',
                'items.product',
                'items.productVariation',
                'items.productVariation.attributes.value.attribute',
                'items.productVariation.attributes.value'
            ])
            ->firstOrFail();

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
            $input = $request->input('track_input');

            $orders = \App\Models\Order::query()
                ->where('order_number', $input)
                ->orWhere('customer_phone', $input)
             ->with([
                'items',
                'items.product',
                'items.productVariation',
                'items.productVariation.attributes.value.attribute',
                'items.productVariation.attributes.value'
            ])
                ->orderBy('created_at', 'desc')
                ->get();
        }

        return Inertia::render('Frontend/Order/TrackOrder', [
            'orders' => $orders,
            'filters' => $request->only(['track_input']),
            'searched' => $searched
        ]);
    }
}
