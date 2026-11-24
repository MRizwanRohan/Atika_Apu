<?php

namespace App\Http\Controllers\Frontend\Home;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;
use App\Services\ProductGroup\ProductGroupService;
use App\Services\Product\FeaturedProductService;
use App\Services\Recipe\RecipeService;
use App\Models\Slider;
use App\Models\ProductVideo;
use App\Models\ProductGroup;
use Illuminate\Support\Facades\Cache;

use App\Models\InstagramFeed;

class HomeController extends Controller
{
    public function __construct(
        protected ProductService $productService,
        protected ProductGroupService $productGroupService,
        protected FeaturedProductService $featuredProductService,
        protected RecipeService $recipeService
    ) {}


    /**
     * Display the home/welcome page.
     */
    public function index(): Response
    {
        return Inertia::render('Frontend/Home/Home', [
            'sliders' => fn () => $this->sliders(),
            // Near the top of the page, so sent with the first response instead of lazy-loaded.
            'sauces' => fn () => $this->productService->sauceProducts(),
            // Small and cached, so it ships with the page and never needs a placeholder that resizes.
            'recipeSection' => fn () => $this->recipeService->forHomepage(),
            // Which lazy sections have anything to show; empty ones get no placeholder, so nothing collapses.
            'homeSections' => fn () => $this->homeSections(),
            // Below-fold data is requested only when its section approaches the viewport.
            'productGroups' => Inertia::optional(fn () => $this->productGroupService->homepagePage()),
            'featuredProduct' => Inertia::optional(fn () => $this->featuredProductService->forHomepage()),
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


    private function homeSections(): array
    {
        $published = fn ($q) => $q->where('status', 'Published');

        return [
            'videos' => ProductVideo::whereNotNull('video')->whereHas('product', $published)->exists(),
            'productGroups' => ProductGroup::where('status', true)->whereHas('products', $published)->exists()
                || $this->featuredProductService->forHomepage() !== null,
            'instagram' => InstagramFeed::active()->exists(),
        ];
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
