<?php

namespace App\Http\Controllers\Admin\InstagramFeed;

use App\Http\Controllers\Controller;
use App\Services\InstagramFeed\InstagramFeedService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

class InstagramFeedController extends Controller
{
    protected $service;

    public function __construct(InstagramFeedService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $feeds = $this->service->list();
        return Inertia::render('Admin/InstagramFeed/Index', [
            'feeds' => $feeds
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Admin/InstagramFeed/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|url',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->service->store($request->all());

        return redirect()->route('admin.instagram-feeds.index')->with('success', 'Instagram feed created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $feed = $this->service->getById($id);
        return Inertia::render('Admin/InstagramFeed/Edit', [
            'feed' => $feed
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|url',
            'status' => 'boolean',
            'sort_order' => 'integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->service->update($id, $request->all());

        return redirect()->route('admin.instagram-feeds.index')->with('success', 'Instagram feed updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->service->destroy($id);
        return back()->with('success', 'Instagram feed deleted successfully.');
    }

    /**
     * Toggle status of the resource.
     */
    public function toggleStatus($id)
    {
        $this->service->toggleStatus($id);
        return back()->with('success', 'Status updated successfully.');
    }

    /**
     * Reorder the resources.
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:instagram_feeds,id',
        ]);

        $this->service->reorder($request->ids);

        return back()->with('success', 'Order updated successfully.');
    }
}
