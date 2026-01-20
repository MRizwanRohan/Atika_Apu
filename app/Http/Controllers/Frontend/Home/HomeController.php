<?php

namespace App\Http\Controllers\Frontend\Home;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Services\Product\ProductService;
use App\Services\ProductGroup\ProductGroupService;
use App\Models\Slider;
use Illuminate\Support\Facades\Cache;

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

        // Categories are shared globally via HandleInertiaRequests middleware
        return Inertia::render('Frontend/Home/Home', [
            'groupedProducts' => $groupedProducts,
            'activeCampaigns' => $activeCampaigns,
            'productGroups' => $productGroups,
            'sliders' => $sliders,
        ]);
    }


    private function sliders()
    {
        $sliders = Cache::remember('sliders.all', 3600, function () {
            return Slider::all();
        });
        return $sliders;
    }
}
