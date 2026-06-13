<?php

namespace App\Http\Controllers\Admin\GeneralSetting;

use App\Http\Controllers\Controller;
use App\Http\Requests\GeneralSetting\PopupSettingRequest;
use App\Services\GeneralSetting\GeneralSettingService;
use App\Helpers\ImageHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class PopupSettingController extends Controller
{
    protected $generalSettingService;

    public function __construct(GeneralSettingService $generalSettingService)
    {
        $this->generalSettingService = $generalSettingService;
    }

    public function index()
    {
        $settings = $this->generalSettingService->getSettings();

        return Inertia::render('Admin/GeneralSettings/PopupSettings', [
            'settings' => $settings,
        ]);
    }

    public function store(PopupSettingRequest $request)
    {
        $validatedData = $request->validated();
        
        $settings = $this->generalSettingService->getSettings();

        if ($request->hasFile('popup_image_file')) {
            // Delete old image if exists
            if ($settings && $settings->popup_image && str_contains($settings->popup_image, 'storage/')) {
                ImageHelper::deleteImage($settings->popup_image);
            }

            // Upload and save new image
            $validatedData['popup_image'] = ImageHelper::uploadImage($request->file('popup_image_file'), 'storage/popup');
        }

        unset($validatedData['popup_image_file']);
        
        $this->generalSettingService->updateOrCreateSettings($validatedData);

        Cache::forget('site_info');
        Cache::forget('general_settings');

        return redirect()->back()->with('success', 'Popup settings updated successfully');
    }
}
