<?php

namespace App\Http\Controllers\Admin\Sms;

use App\Http\Controllers\Controller;
use App\Models\SmsSetting;
use App\Models\SmsTemplate;
use App\Services\Sms\SmsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmsSettingController extends Controller
{
    /**
     * Display SMS settings and templates
     */
    public function index(SmsService $smsService)
    {
        $settings = SmsSetting::first();
        if (!$settings) {
            $settings = SmsSetting::create([
                'is_enabled' => false,
                'order_create_sms_enabled' => false,
                'order_create_sms_template' => "Hello {name}, Your order #{order_number} has been placed. Total: ৳{total}. Thank you for shopping with us!"
            ]);
        }

        $templates = SmsTemplate::orderBy('created_at', 'desc')->get();
        $balance = $smsService->getBalance();

        return Inertia::render('Admin/Settings/SmsSettings', [
            'settings' => $settings,
            'templates' => $templates,
            'balance' => $balance,
        ]);
    }

    /**
     * Update global SMS settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'api_key' => 'required|string',
            'sender_id' => 'required|string',
            'is_enabled' => 'required|boolean',
            'order_create_sms_enabled' => 'required|boolean',
            'order_create_sms_template' => 'nullable|string',
        ]);

        $settings = SmsSetting::first();
        $settings->update([
            'api_key' => $request->api_key,
            'sender_id' => $request->sender_id,
            'is_enabled' => $request->is_enabled,
            'order_create_sms_enabled' => $request->order_create_sms_enabled,
            'order_create_sms_template' => $request->order_create_sms_template,
        ]);

        return back()->with('success', 'SMS settings updated successfully.');
    }

    /**
     * Store a new bulk SMS template
     */
    public function storeTemplate(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        SmsTemplate::create($request->only(['name', 'content']));

        return back()->with('success', 'SMS template created successfully.');
    }

    /**
     * Update an existing SMS template
     */
    public function updateTemplate(Request $request, SmsTemplate $template)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $template->update($request->only(['name', 'content']));

        return back()->with('success', 'SMS template updated successfully.');
    }

    /**
     * Delete an SMS template
     */
    public function destroyTemplate(SmsTemplate $template)
    {
        $template->delete();
        return back()->with('success', 'SMS template deleted successfully.');
    }
}
