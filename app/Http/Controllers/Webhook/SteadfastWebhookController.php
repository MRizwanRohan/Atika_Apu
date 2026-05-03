<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\CourierSetting;
use App\Services\Webhook\WebhookStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SteadfastWebhookController extends Controller
{
    private WebhookStatusService $webhookService;

    public function __construct(WebhookStatusService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle incoming Steadfast webhook
     *
     * Steadfast Requirements:
     * - Validates Authorization: Bearer {token}
     * - Returns HTTP 200 with success JSON
     */
    public function handle(Request $request)
    {
        $settings = CourierSetting::first();

        // Check if webhook is enabled
        if (!$settings || $settings->steadfast_webhook_enabled !== 'yes') {
            Log::warning('Steadfast webhook received but disabled');
            return response()->json(['status' => 'error', 'message' => 'Webhook disabled'], 403);
        }

        $token = $settings->steadfast_webhook_token;

        // Validate Bearer token
        $authHeader = $request->header('Authorization');
        $bearerToken = null;
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $bearerToken = substr($authHeader, 7);
        }

        if (!$bearerToken || $bearerToken !== $token) {
            Log::warning('Steadfast webhook: Invalid auth token');
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        $payload = $request->all();

        Log::info('Steadfast webhook received', [
            'notification_type' => $payload['notification_type'] ?? 'unknown',
            'consignment_id' => $payload['consignment_id'] ?? null,
        ]);

        // Process the webhook event
        try {
            $this->webhookService->processSteadfastWebhook($payload);
        } catch (\Exception $e) {
            Log::error('Steadfast webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Processing failed',
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook received successfully.',
        ], 200);
    }
}
