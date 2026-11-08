<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Models\FeaturedProductSetting;
use App\Models\Product;
use App\Services\Product\FeaturedProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FeaturedProductController extends Controller
{
    private const SUMMARY_COLUMNS = ['id', 'name', 'product_code', 'feature_image', 'price', 'status'];

    public function __construct(protected FeaturedProductService $service) {}

    public function index(): Response
    {
        $setting = FeaturedProductSetting::current();

        return Inertia::render('Admin/Product/Featured', [
            'setting' => [
                'enabled' => $setting->enabled,
                'product_id' => $setting->product_id,
                'badge' => $setting->badge ?? FeaturedProductSetting::DEFAULT_BADGE,
                'description' => $setting->description ?? '',
                'highlights' => array_pad($setting->highlights ?? [], 3, ''),
                'bg_color' => $setting->bg_color ?? FeaturedProductSetting::DEFAULT_BG_COLOR,
            ],
            'selectedProduct' => $setting->product_id
                ? Product::select(self::SUMMARY_COLUMNS)->find($setting->product_id)
                : null,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'product_id' => [
                Rule::requiredIf(fn () => $request->boolean('enabled')),
                'nullable',
                'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at')->where('status', 'Published'),
            ],
            'badge' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:300'],
            'highlights' => ['nullable', 'array', 'max:3'],
            'highlights.*' => ['nullable', 'string', 'max:30'],
            'bg_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'product_id.required' => 'Select a product to feature, or turn the section off.',
            'product_id.exists' => 'That product is not published.',
        ]);

        $data['highlights'] = array_values(array_filter($data['highlights'] ?? [], 'filled'));

        $this->service->save($data);

        return back()->with('success', 'Featured product updated.');
    }

    public function search(Request $request): JsonResponse
    {
        $query = trim((string) ($request->validate(['query' => ['nullable', 'string', 'max:100']])['query'] ?? ''));
        if ($query === '') {
            return response()->json([]);
        }

        return response()->json(
            Product::select(self::SUMMARY_COLUMNS)
                ->where('status', 'Published')
                ->where(fn ($q) => $q->where('name', 'like', "%{$query}%")->orWhere('product_code', 'like', "%{$query}%"))
                ->orderBy('name')
                ->limit(10)
                ->get()
        );
    }
}
