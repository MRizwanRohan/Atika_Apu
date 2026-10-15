<?php

namespace App\Http\Controllers\Frontend\Home;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;
use App\Services\ProductGroup\ProductGroupService;
use App\Models\Slider;
use App\Models\ProductVideo;
use Illuminate\Support\Facades\Cache;

use App\Models\InstagramFeed;

class HomeController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected ProductGroupService $productGroupService
    ) {}


    /**
     * Display the home/welcome page.
     */
    public function index(): Response
    {
        return Inertia::render('Frontend/Home/Home', [
            'sliders' => fn () => $this->sliders(),
            // Below-fold data is requested only when its section approaches the viewport.
            'groupedProducts' => Inertia::optional(fn () => $this->productService->categorywithproducts()),
            'productGroups' => Inertia::optional(fn () => $this->productGroupService->homepagePage()),
            'productswithVideo' => Inertia::optional(fn () => $this->productVideos()),
            'instagramFeeds' => Inertia::optional(fn () => Cache::remember('instagram_feeds.active', 3600,
                fn () => InstagramFeed::active()->orderBy('sort_order')->get(['id', 'image', 'link'])
            )),
        ]);
    }

    public function productGroups(Request $request): JsonResponse
    {
        $validated = $request->validate(['page' => 'sometimes|integer|min:1|max:10000']);
        return response()->json($this->productGroupService->homepagePage((int) ($validated['page'] ?? 1)));
    }


    private function sliders()
    {
        $sliders = Cache::remember('sliders.all', 3600, function () {
            return Slider::all();
        });
        return $sliders;
    }

    private function productVideos()
    {
        return ProductVideo::whereHas('product', function($query) {
            $query->where('status', 'Published');
        })
        ->with(['product' => function($query) {
            $query->select('id', 'name', 'product_code', 'slug', 'price', 'previous_price', 'feature_image', 'type')
                  ->with(['variations' => function($v) {
                      $v->select('id', 'product_id', 'price');
                  }]);
        }])
        ->whereNotNull('video')
        ->orderBy('created_at', 'desc')
        ->take(30)
        ->get(['id', 'product_id', 'title', 'video', 'link'])
        ->each(fn ($video) => $video->product?->setAppends(['responsive_image']));
    }
}
