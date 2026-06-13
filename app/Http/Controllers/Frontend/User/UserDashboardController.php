<?php

namespace App\Http\Controllers\Frontend\User;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Order;
use Illuminate\Routing\Controllers\HasMiddleware;

class UserDashboardController extends Controller implements HasMiddleware
{
    /**
     * Get the middleware that should be assigned to the controller.
     */
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                $user = Auth::user();
                if ($user && $user->roles()->exists() && !$user->hasRole('customer')) {
                    return redirect()->route('admin.dashboard.index');
                }
                return $next($request);
            }
        ];
    }

    /**
     * Display the user dashboard.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        // Get user's orders excluding incomplete orders with product relationships
        $orders = Order::where('user_id', $user->id)
            ->where('status', '!=', 'incomplete')
            ->with(
                'items',
                'items.product',
                'items.productVariation',
                'items.productVariation.attributes.value.attribute',
                'items.productVariation.attributes.value'
            )
            ->latest()
            ->paginate(10);

        // Get order statistics (excluding incomplete)
        $totalOrders = Order::where('user_id', $user->id)
            ->where('status', '!=', 'incomplete')
            ->count();

        $pendingOrders = Order::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        $completedOrders = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->count();

        $incompleteOrdersCount = Order::where('user_id', $user->id)
            ->where('status', 'incomplete')
            ->count();

        $canceledOrders = Order::where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->count();

        $preOrderLeads = \App\Models\PreOrderLead::where(function($query) use ($user) {
                if ($user->email) $query->where('email', $user->email);
                if ($user->phone_number) $query->orWhere('phone', $user->phone_number);
            })->latest()->get();

        $preOrderOrders = Order::where('user_id', $user->id)
            ->where('status', '!=', 'incomplete')
            ->whereHas('items', function($query) {
                $query->where('is_pre_order', true);
            })
            ->with(['items' => function($q) {
                $q->where('is_pre_order', true)->with('product:id,name,feature_image,slug');
            }])
            ->latest()
            ->get();

        $reviews = \App\Models\Review::where('user_id', $user->id)
            ->with('product:id,name,feature_image,slug')
            ->latest()
            ->get();

        // Orders that can be reviewed (Delivered)
        $reviewableOrders = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->with('items.product:id,name,feature_image,slug')
            ->latest()
            ->get();

        return Inertia::render('Frontend/User/Dashboard', [
            'user' => $user,
            'orders' => $orders,
            'preOrderLeads' => $preOrderLeads,
            'preOrderOrders' => $preOrderOrders,
            'reviews' => $reviews,
            'reviewableOrders' => $reviewableOrders,
            'stats' => [
                'totalOrders' => $totalOrders,
                'pendingOrders' => $pendingOrders,
                'completedOrders' => $completedOrders,
                'canceledOrders' => $canceledOrders,
                'incompleteOrders' => $incompleteOrdersCount,
                'preOrderCount' => $preOrderLeads->count() + $preOrderOrders->count(),
                'reviewsCount' => $reviews->count(),
            ],
            'general_settings' => \App\Models\GeneralSetting::first(),
        ]);
    }

    /**
     * Show all user orders.
     */
    public function orders(Request $request): Response
    {
        $user = Auth::user();
        $status = $request->input('status');

        $query = Order::where('user_id', $user->id)
            ->where('status', '!=', 'incomplete');

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->with(['items.product:id,name,feature_image,slug'])
            ->latest()
            ->paginate(10);

        return Inertia::render('Frontend/User/Orders', [
            'orders' => $orders,
            'status' => $status,
        ]);
    }

    /**
     * Show incomplete orders.
     */
    public function incompleteOrders(Request $request): Response
    {
        $user = Auth::user();
        $orders = Order::where('user_id', $user->id)
            ->where('status', 'incomplete')
            ->with(['items.product:id,name,feature_image,slug'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Frontend/User/IncompleteOrders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show the profile edit form.
     */
    public function editProfile(Request $request): Response
    {
        return Inertia::render('Frontend/User/Profile', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Show the password edit form.
     */
    public function editPassword(Request $request): Response
    {
        return Inertia::render('Frontend/User/Password', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Show user reviews.
     */
    public function reviews(Request $request): Response
    {
        $user = Auth::user();
        $reviews = \App\Models\Review::where('user_id', $user->id)
            ->with('product:id,name,feature_image,slug')
            ->latest()
            ->paginate(15);

        $reviewableOrders = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->with('items.product:id,name,feature_image,slug')
            ->latest()
            ->get();

        return Inertia::render('Frontend/User/Reviews', [
            'reviews' => $reviews,
            'reviewableOrders' => $reviewableOrders,
        ]);
    }

    /**
     * Show user pre-orders.
     */
    public function preOrders(Request $request): Response
    {
        $user = Auth::user();
        $preOrderLeads = \App\Models\PreOrderLead::where(function($query) use ($user) {
                if ($user->email) $query->where('email', $user->email);
                if ($user->phone_number) $query->orWhere('phone', $user->phone_number);
            })->latest()->paginate(15);

        $preOrderOrders = Order::where('user_id', $user->id)
            ->where('status', '!=', 'incomplete')
            ->whereHas('items', function($query) {
                $query->where('is_pre_order', true);
            })
            ->with(['items' => function($q) {
                $q->where('is_pre_order', true)->with('product:id,name,feature_image');
            }])
            ->latest()
            ->get();

        return Inertia::render('Frontend/User/PreOrders', [
            'preOrderLeads' => $preOrderLeads,
            'preOrderOrders' => $preOrderOrders,
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        $user->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $user->password)) {
            return redirect()->back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->back()->with('success', 'Password updated successfully');
    }
}
