<?php

namespace App\Http\Controllers\Admin\Courier;

use Inertia\Inertia;
use App\Models\Order;
use App\Models\WebhookLog;
use Illuminate\Http\Request;
use App\Models\CourierSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Couriers\Pathao\PathaoService;
use App\Services\Webhook\WebhookTokenService;
use App\Services\Webhook\WebhookStatusService;

class CourierController extends Controller
{
    protected $pathaoService;
    protected $tokenService;
    protected $webhookStatusService;

    public function __construct(PathaoService $pathaoService, WebhookTokenService $tokenService, WebhookStatusService $webhookStatusService)
    {
        $this->pathaoService = $pathaoService;
        $this->tokenService = $tokenService;
        $this->webhookStatusService = $webhookStatusService;
    }

    public function index()
    {
        $settings = CourierSetting::first();
        return Inertia::render('Admin/Courier/Index', [
            'settings' => $settings ? [
                'id' => $settings->id,
                'client_id' => $settings->client_id,
                'client_secret' => $settings->client_secret,
                'username' => $settings->username,
                'password' => $settings->password,
                'is_enabled' => $settings->is_enabled,
                'StoreId' => $settings->StoreId,
                'access_token' => $settings->access_token,
                'expires_at' => $settings->expires_at ? $settings->expires_at->toDateTimeString() : null,
                'steadfast_api_key' => $settings->steadfast_api_key,
                'steadfast_secret_key' => $settings->steadfast_secret_key,
                'steadfast_is_enabled' => $settings->steadfast_is_enabled,
                // Webhook fields
                'pathao_webhook_enabled' => $settings->pathao_webhook_enabled ?? 'no',
                'pathao_webhook_token' => $settings->pathao_webhook_token,
                'pathao_status_mapping' => $settings->pathao_status_mapping ?? $this->webhookStatusService->getDefaultPathaoMapping(),
                'steadfast_webhook_enabled' => $settings->steadfast_webhook_enabled ?? 'no',
                'steadfast_webhook_token' => $settings->steadfast_webhook_token,
                'steadfast_status_mapping' => $settings->steadfast_status_mapping ?? $this->webhookStatusService->getDefaultSteadfastMapping(),
            ] : null,
            'pathaoWebhookUrl' => url('/webhook/pathao'),
            'steadfastWebhookUrl' => url('/webhook/steadfast'),
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        try {
            $rules = [];

            // Pathao validation rules
            if ($request->hasAny(['client_id', 'client_secret', 'username', 'password', 'is_enabled', 'StoreId', 'pathao_webhook_enabled'])) {
                $rules = array_merge($rules, [
                    'client_id' => 'nullable|string',
                    'client_secret' => 'nullable|string',
                    'username' => 'nullable|string',
                    'password' => 'nullable|string',
                    'is_enabled' => 'nullable|in:yes,no',
                    'StoreId' => 'nullable|integer',
                    'pathao_webhook_enabled' => 'nullable|in:yes,no',
                ]);
            }

            // Steadfast validation rules
            if ($request->hasAny(['steadfast_api_key', 'steadfast_secret_key', 'steadfast_is_enabled', 'steadfast_webhook_enabled'])) {
                $rules = array_merge($rules, [
                    'steadfast_api_key' => 'nullable|string',
                    'steadfast_secret_key' => 'nullable|string',
                    'steadfast_is_enabled' => 'nullable|in:yes,no',
                    'steadfast_webhook_enabled' => 'nullable|in:yes,no',
                ]);
            }

            if (empty($rules)) {
                return redirect()->back()->with(['error' => 'No settings provided']);
            }

            $validated = $request->validate($rules);

            $settings = CourierSetting::first();

            if ($settings) {
                // Check if Pathao credentials changed to force a new token
                if (isset($validated['client_id'])) {
                    $credentialsChanged = ($settings->client_id !== $validated['client_id']) ||
                        ($settings->client_secret !== $validated['client_secret']) ||
                        ($settings->username !== $validated['username']) ||
                        ($settings->password !== $validated['password']);

                    if ($credentialsChanged) {
                        $validated['access_token'] = null;
                        $validated['expires_at'] = null;
                    }
                }

                $settings->update($validated);
                $message = 'Courier settings updated successfully';
            } else {
                CourierSetting::create($validated);
                $message = 'Courier settings created successfully';
            }

            return redirect()->back()->with([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Courier Settings Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->with([
                'error' => $e->getMessage(),
            ]);
        }
    }


    /**
     * Get zones for a specific city
     */
    public function getZonesByCity($cityId)
    {
        try {
            $response = $this->pathaoService->getZonesByCity($cityId);
            return response()->json($response['data'] ?? [], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Get areas for a specific zone
     */
    public function getAreasByZone($zoneId)
    {
        try {
            $response = $this->pathaoService->getAreasByZone($zoneId);
            return response()->json($response['data'] ?? [], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    /**
     * Get all cities (already available in your PathaoService)
     */
    public function getAllCities()
    {
        try {
            $response = $this->pathaoService->getCities();
            return response()->json($response['data'] ?? [], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Create a new courier shipment with Pathao
     */
    public function createShipment(Request $request)
    {
        try {
            $validated = $request->validate([
                'orderId' => 'required|integer',
            ]);

            // Call the Pathao service to create the shipment
            $response = $this->pathaoService->createShipment($validated, $request->input('specialInstruction', 'Need to Delivery before 5 PM'));

            if (isset($response['code']) && $response['code'] === 200) {
                return redirect()->back()->with('flash', [
                    'message' => $response['message'] ?? 'Order sent to Pathao successfully',
                    'type' => 'success'
                ]);
            }

            return redirect()->back()->withErrors([
                'message' => $response['message'] ?? 'Failed to create Pathao shipment'
            ]);
        } catch (\Exception $e) {
            Log::error('Pathao Shipment Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors([
                'message' => $e->getMessage()
            ]);
        }
    }


    /**
     * Create a bulk courier shipment with Pathao
     */
    public function createBulkShipment(Request $request)
    {
        try {
            $validated = $request->validate([
                'orders' => 'required|array',
                'orders.*.orderId' => 'required|integer',
            ]);

            $response = $this->pathaoService->createBulkShipment($validated);

            if (isset($response['code']) && $response['code'] === 200) {
                return redirect()->back()->with('flash', [
                    'message' => $response['message'] ?? 'Bulk order creation request completed',
                    'type' => 'success'
                ]);
            }

            return redirect()->back()->withErrors([
                'message' => $response['message'] ?? 'Failed to create Pathao bulk shipment'
            ]);
        } catch (\Exception $e) {
            Log::error('Pathao Bulk Shipment Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->withErrors([
                'message' => $e->getMessage()
            ]);
        }
    }


    /**
     * Generate or regenerate webhook token for a courier
     */
    public function generateWebhookToken(Request $request, string $courier)
    {
        try {
            if (!in_array($courier, ['pathao', 'steadfast'])) {
                return redirect()->back()->with('error', 'Invalid courier type');
            }

            $token = $this->tokenService->generateToken($courier);

            return redirect()->back()->with([
                'success' => true,
                'message' => ucfirst($courier) . ' webhook token generated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Token Generation Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Save status mapping for a courier
     */
    public function saveStatusMapping(Request $request, string $courier)
    {
        try {
            if (!in_array($courier, ['pathao', 'steadfast'])) {
                return redirect()->back()->with('error', 'Invalid courier type');
            }

            $validated = $request->validate([
                'mapping' => 'required|array',
            ]);

            $settings = CourierSetting::first();
            if (!$settings) {
                $settings = CourierSetting::create([]);
            }

            $field = $courier === 'pathao' ? 'pathao_status_mapping' : 'steadfast_status_mapping';
            $settings->update([$field => $validated['mapping']]);

            return redirect()->back()->with([
                'success' => true,
                'message' => ucfirst($courier) . ' status mapping saved successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Status Mapping Error:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * View webhook logs
     */
    /**
     * View webhook logs
     */
    public function webhookLogs(Request $request)
    {
        $logs = WebhookLog::orderBy('created_at', 'desc')
            ->when($request->input('courier'), fn($q, $v) => $q->where('courier', $v))
            ->when($request->input('status'), fn($q, $v) => $q->where('status', $v))
            ->paginate(20);

        return Inertia::render('Admin/Courier/WebhookLogs', [
            'logs' => $logs,
        ]);
    }

    /**
     * View courier dashboard with reports
     */
    public function dashboard(Request $request)
    {
        $stats = [
            'total' => $this->getOrderByStatus(null, $request),
            'dhaka' => $this->getOrderByStatus('Dhaka', $request),
            'outside' => $this->getOrderByStatus('outside', $request),
        ];

        return Inertia::render('Admin/Courier/Dashboard', [
            'stats' => $stats,
            'locations' => $this->districtWiseOrders($request),
            'filters' => [
                'period' => $request->input('period', 'all'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ]
        ]);
    }

    private function districtWiseOrders(Request $request = null)
    {
        $query = \Illuminate\Support\Facades\DB::table('orders')
            ->select('shipping_district as district', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_orders'))
            ->where('is_courier', 1)
            ->whereNotNull('shipping_district');

        if ($request) {
            $this->applyDateFilters($query, $request);
        }

        return $query->groupBy('shipping_district')
            ->orderByRaw('COUNT(*) DESC')
            ->get();
    }

    private function getOrderByStatus($district = null, Request $request = null)
    {
        $query = Order::where('is_courier', 1);

        if ($district === 'Dhaka') {
            $query->where('shipping_district', 'Dhaka');
        } elseif ($district === 'outside') {
            $query->where('shipping_district', '!=', 'Dhaka');
        }

        if ($request) {
            $this->applyDateFilters($query, $request);
        }

        return $query->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();
    }

    private function applyDateFilters($query, Request $request)
    {
        $period = $request->input('period', 'all');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($period === 'today') {
            $query->whereDate('created_at', today());
        } elseif ($period === 'weekly') {
            $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'monthly') {
            $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($period === 'custom') {
            if ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            }
        }
    }
}
