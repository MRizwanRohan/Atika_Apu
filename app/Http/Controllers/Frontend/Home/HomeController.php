<?php

namespace App\Http\Controllers\Frontend\Home;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;
use App\Services\ProductGroup\ProductGroupService;
use App\Models\Slider;
use App\Models\ProductVideo;
use Illuminate\Support\Facades\Cache;
use App\Models\Product;

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
        $groupedProducts = $this->productService->categorywithproducts();
        $activeCampaigns = $this->productService->getActiveCampaigns();
        $productGroups = $this->productGroupService->getAllWithProducts();
        $sliders = $this->sliders();
        $productVideos = $this->productVideos();

        // Categories are shared globally via HandleInertiaRequests middleware
        return Inertia::render('Frontend/Home/Home', [
            'groupedProducts' => $groupedProducts,
            'activeCampaigns' => $activeCampaigns,
            'productGroups' => $productGroups,
            'sliders' => $sliders,
            'productswithVideo' => $productVideos,
        ]);
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
            $query->where('status', 'published');
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
        ->get();
    }
}
