<?php

namespace App\Http\Controllers\Admin\GeneralSetting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\DeliveryZone;
use Inertia\Inertia;

class DeliveryChargeController extends Controller
{
    public function index()
    {
        $districts = DeliveryZone::where('type', 'district')
            ->withCount(['children as custom_thanas_count' => function ($query) {
                $query->whereNotNull('charge');
            }])
            ->orderBy('name')
            ->get();

        $settings = \App\Models\GeneralSetting::first();

        return Inertia::render('Admin/GeneralSettings/DeliveryCharges/Index', [
            'districts' => $districts,
            'settings' => $settings
        ]);
    }

    public function getThanas(DeliveryZone $district)
    {
        return response()->json($district->children()->orderBy('name')->get());
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'shipping_charge_inside_dhaka' => 'required|numeric|min:0',
            'shipping_charge_outside_dhaka' => 'required|numeric|min:0',
        ]);

        $settings = \App\Models\GeneralSetting::first() ?? new \App\Models\GeneralSetting();
        $settings->fill($request->only(['shipping_charge_inside_dhaka', 'shipping_charge_outside_dhaka']));
        $settings->save();

        return redirect()->back()->with('success', 'Global shipping charges updated successfully');
    }

    public function update(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:delivery_zones,id',
            'charge' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $zone = DeliveryZone::findOrFail($request->id);
        $zone->update([
            'charge' => $request->charge,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->back()->with('success', 'Delivery charge updated successfully');
    }

    public function bulkUpdateThanas(Request $request)
    {
        $request->validate([
            'district_id' => 'required|exists:delivery_zones,id',
            'charge' => 'required|numeric|min:0',
        ]);

        DeliveryZone::where('parent_id', $request->district_id)
            ->update(['charge' => $request->charge]);

        return redirect()->back()->with('success', 'All thanas in district updated successfully');
    }
}
