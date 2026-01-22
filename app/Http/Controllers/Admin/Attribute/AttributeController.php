<?php

namespace App\Http\Controllers\Admin\Attribute;

use Inertia\Inertia;
use App\Models\Attribute;
use Illuminate\Http\Request;
use App\Models\AttributeValue;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $attrList = Attribute::with(['values' => function ($query) {
    $query->orderBy('value', 'asc');
}])->get();


        return Inertia::render('Admin/Attribute/Index', [
            'attrList' => $attrList,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function Create(){

    }

    /**
     * Store a newly created resource in storage.
     */
    // public function store(Request $request)
    // {

    //     // Create the attribute
    //     $attribute = Attribute::create([
    //         'name' => $request->name,
    //     ]);


    //     foreach ($request->values as $value) {
    //         AttributeValue::create([
    //             'attribute_id' => $attribute->id,
    //             'value' => $value['value'],
    //             'color' => $value['color'] ?? null,
    //         ]);
    //     }

    //     return redirect()->route('admin.attributes.create')->with('success', 'Attribute and its values created successfully.');
    // }


public function store(Request $request)
{
    $request->validate([
        'name' => 'required|string|unique:attributes,name,NULL,id,deleted_at,NULL',
        'values' => 'required|array',
        'values.*.value' => 'required|string',
    ]);

    // Check for duplicate values in the request
    $values = collect($request->values)->pluck('value')->map(fn($v) => Str::lower(trim($v)));
    if ($values->count() !== $values->unique()->count()) {
        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Duplicate attribute values are not allowed.');
    }    DB::beginTransaction();

    try {

        // Create attribute (lowercase enforced)
        $attribute = Attribute::create([
            'name' => Str::upper(trim($request->name)),
        ]);

        // Create or restore attribute values
        foreach ($request->values as $value) {
            $valueText = Str::upper(trim($value['value']));

            // Check if soft-deleted value exists
            $existingValue = AttributeValue::withTrashed()
                ->where('attribute_id', $attribute->id)
                ->where('value', $valueText)
                ->first();

            if ($existingValue && $existingValue->trashed()) {
                // Restore soft-deleted value
                $existingValue->restore();
                $existingValue->update(['color' => $value['color'] ?? null]);
            } else {
                // Create new value
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    'value'        => $valueText,
                    'color'        => $value['color'] ?? null,
                ]);
            }
        }

        DB::commit();

        // Clear attributes cache so new attribute appears immediately
        Cache::forget('attributes.all');

        return redirect()
            ->route('admin.attributes.create')
            ->with('success', 'Attribute and its values created successfully.');

    } catch (\Exception $e) {

        DB::rollBack();

        return redirect()
            ->back()
            ->withInput()
            ->with('error', 'Something went wrong. Please try again.');
    }
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $attribute = Attribute::with(['values' => function ($query) {
    $query->orderBy('value', 'asc');
}])->findOrFail($id);

        return Inertia::render('Admin/Attribute/Index', [
            'attrList' => Attribute::with(['values' => function ($query) {
    $query->orderBy('value', 'asc');
}])->get(),
            'editingAttribute' => $attribute
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $attribute = Attribute::findOrFail($id);

        // Validate request
        $request->validate([
            'name' => 'required|string|unique:attributes,name,' . $attribute->id . ',id,deleted_at,NULL',
            'values' => 'required|array',
            'values.*.value' => 'required|string',
        ]);

        // Check for duplicate values in the request
        $values = collect($request->values)->pluck('value')->map(fn($v) => Str::lower(trim($v)));
        if ($values->count() !== $values->unique()->count()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Duplicate attribute values are not allowed.');
        }

        // Update attribute name
        $attribute->update([
            'name' => $request->name,
        ]);

        // Process values
        $existingIds = [];
        foreach ($request->values as $value) {
            $valueText = Str::upper(trim($value['value']));

            if (isset($value['id'])) {
                // Update existing value
                AttributeValue::where('id', $value['id'])->update([
                    'value' => $valueText,
                    'color' => $value['color'] ?? null,
                ]);
                $existingIds[] = $value['id'];
            } else {
                // Check if value already exists (active or soft-deleted)
                $existingValue = AttributeValue::withTrashed()
                    ->where('attribute_id', $attribute->id)
                    ->where('value', $valueText)
                    ->first();

                if ($existingValue) {
                    // Value exists - restore if soft-deleted and update color
                    if ($existingValue->trashed()) {
                        $existingValue->restore();
                    }
                    $existingValue->update(['color' => $value['color'] ?? null]);
                    $existingIds[] = $existingValue->id;
                } else {
                    // Create new value (doesn't exist at all)
                    $newValue = AttributeValue::create([
                        'attribute_id' => $attribute->id,
                        'value' => $valueText,
                        'color' => $value['color'] ?? null,
                    ]);
                    $existingIds[] = $newValue->id;
                }
            }
        }

        // Delete removed values
        AttributeValue::where('attribute_id', $attribute->id)
            ->whereNotIn('id', $existingIds)
            ->delete();

        // Clear attributes cache
        Cache::forget('attributes.all');

        return redirect()->route('admin.attributes.index')->with('success', 'Attribute updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $attribute = Attribute::findOrFail($id);

        $attributeValues = AttributeValue::where('attribute_id', $id)->get();

        // Check if any attribute value is used in orders or variations with sold stock
        $hasOrders = false;
        $hasSoldStock = false;

        foreach ($attributeValues as $value) {
            // Check if used in any order items
            $orderItemsCount = \DB::table('order_items')
                ->join('variation_attributes', 'order_items.product_variation_id', '=', 'variation_attributes.product_variation_id')
                ->where('variation_attributes.attribute_value_id', $value->id)
                ->count();

            if ($orderItemsCount > 0) {
                $hasOrders = true;
            }

            // Check if used in variations with sold stock
            $soldStockCount = \DB::table('product_variations')
                ->join('variation_attributes', 'product_variations.id', '=', 'variation_attributes.product_variation_id')
                ->where('variation_attributes.attribute_value_id', $value->id)
                ->where('product_variations.sold_stock', '>', 0)
                ->count();

            if ($soldStockCount > 0) {
                $hasSoldStock = true;
            }
        }

        // Add warning message if attribute is used in historical data
        $warningMessage = '';
        if ($hasOrders || $hasSoldStock) {
            $warningMessage = ' (Note: This attribute was used in ';
            $parts = [];
            if ($hasOrders) $parts[] = 'past orders';
            if ($hasSoldStock) $parts[] = 'sold products';
            $warningMessage .= implode(' and ', $parts) . '. Historical data will still show this attribute.)';
        }

        // SOFT DELETE: Both attribute and values are soft deleted (deleted_at timestamp set)
        // Data is preserved for product history and can be restored if needed

        // Also soft delete all product variations that use these attribute values
        $variationIds = \DB::table('variation_attributes')
            ->whereIn('attribute_value_id', $attributeValues->pluck('id'))
            ->pluck('product_variation_id')
            ->unique();

        if ($variationIds->count() > 0) {
            \App\Models\ProductVariation::whereIn('id', $variationIds)->delete();

            // Also soft delete the variation_attributes (if the table has soft delete)
            // Note: variation_attributes typically doesn't have soft delete,
            // but the variations themselves do
        }

        foreach ($attributeValues as $value) {
            $value->delete();
        }

        $attribute->delete();

        // Clear attributes cache and product caches
        Cache::forget('attributes.all');
        Cache::flush(); // Clear all caches to ensure product lists are updated

        return redirect()->route('admin.attributes.index')
            ->with('success', 'Attribute, its values, and related product variations deleted successfully.' . $warningMessage);
    }
}
