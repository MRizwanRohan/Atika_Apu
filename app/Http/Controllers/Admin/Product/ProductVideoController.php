<?php

namespace App\Http\Controllers\Admin\Product;

use App\Contracts\ProductVideoServiceInterface;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductVideoController extends Controller
{
    protected $service;

    public function __construct(ProductVideoServiceInterface $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $videos = $this->service->getAllVideos();
        return Inertia::render('Admin/Product/Video/Index', [
            'videos' => $videos,
        ]);
    }

    public function create()
    {
        $products = Product::select('id', 'name', 'feature_image')->get();
        return Inertia::render('Admin/Product/Video/Create', [
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'video_file' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:20000',
        ]);

        $this->service->storeVideo($request->all());

        return redirect()->route('admin.product-videos.index')->with('success', 'Video added successfully.');
    }

    public function edit($id)
    {
        $video = $this->service->getVideoById($id);
        $products = Product::select('id', 'name', 'feature_image')->get();
        return Inertia::render('Admin/Product/Video/Edit', [
            'video' => $video,
            'products' => $products,
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'title' => 'required|string|max:255',
            'video_file' => 'nullable|file|mimes:mp4,mov,ogg,qt|max:20000',
        ]);

        $this->service->updateVideo($id, $request->all());

        return redirect()->route('admin.product-videos.index')->with('success', 'Video updated successfully.');
    }

    public function destroy($id)
    {
        $this->service->deleteVideo($id);
        return redirect()->route('admin.product-videos.index')->with('success', 'Video deleted successfully.');
    }
}
