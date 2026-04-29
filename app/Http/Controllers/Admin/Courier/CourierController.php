<?php

namespace App\Http\Controllers\Admin\Courier;

use Inertia\Inertia;
use App\Models\Order;
use Illuminate\Http\Request;
use App\Models\CourierSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Services\Couriers\Pathao\PathaoService;

class CourierController extends Controller
{
    protected $pathaoService;

    public function __construct(PathaoService $pathaoService)
    {
        $this->pathaoService = $pathaoService;
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
            ] : null,
        ]);
    }

    public function storeOrUpdate(Request $request)
    {
        try {
            $rules = [];
            
            // Pathao validation rules
            if ($request->hasAny(['client_id', 'client_secret', 'username', 'password', 'is_enabled', 'StoreId'])) {
                $rules = array_merge($rules, [
                    'client_id' => 'required|string',
                    'client_secret' => 'required|string',
                    'username' => 'required|string',
                    'password' => 'required|string',
                    'is_enabled' => 'required|in:yes,no',
                    'StoreId' => 'required|integer',
                ]);
            }

            // Steadfast validation rules
            if ($request->hasAny(['steadfast_api_key', 'steadfast_secret_key', 'steadfast_is_enabled'])) {
                $rules = array_merge($rules, [
                    'steadfast_api_key' => 'required|string',
                    'steadfast_secret_key' => 'required|string',
                    'steadfast_is_enabled' => 'required|in:yes,no',
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

            if (isset($response['code']) && $response['code'] === 202) {
                return redirect()->back()->with('flash', [
                    'message' => $response['message'] ?? 'Bulk order creation request accepted',
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


}
