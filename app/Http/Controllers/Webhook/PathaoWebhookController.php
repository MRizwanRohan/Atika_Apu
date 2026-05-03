<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\CourierSetting;
use App\Services\Webhook\WebhookStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PathaoWebhookController extends Controller
{
    private WebhookStatusService $webhookService;

    public function __construct(WebhookStatusService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle incoming Pathao webhook
     *
     * Pathao Requirements:
     * - Must return HTTP 202
     * - Must return X-Pathao-Merchant-Webhook-Integration-Secret header
     * - Validates X-PATHAO-Signature header
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        $isTestEvent = ($payload['event'] ?? '') === 'webhook_integration';

        $settings = CourierSetting::first();

        // For real events, we need settings and enable check
        if (!$isTestEvent) {
            if (!$settings || $settings->pathao_webhook_enabled !== 'yes') {
                Log::warning('Pathao webhook received but disabled');
                return response()->json(['message' => 'Webhook disabled'], 403);
            }
        }

        // Token from DB if exists
        $token = $settings->pathao_webhook_token ?? '';

        // Handle integration test event first (no signature sent by Pathao for this)
        if ($isTestEvent) {
            Log::info('Pathao webhook integration test received');
            return response()->json([
                'message' => 'Webhook integration verified',
            ], 202)->withHeaders([
                'X-Pathao-Merchant-Webhook-Integration-Secret' => $token,
            ]);
        }

        // Validate signature for all non-test events
        $signature = $request->header('X-PATHAO-Signature');
        if (!$signature || $signature !== $token) {
            Log::warning('Pathao webhook: Invalid signature', [
                'received' => $signature,
                'expected' => $token
            ]);
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        Log::info('Pathao webhook received', ['event' => $payload['event'] ?? 'unknown']);

        // Process the webhook event
        try {
            $this->webhookService->processPathaoWebhook($payload);
        } catch (\Exception $e) {
            Log::error('Pathao webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }

        // Always return 202 with required header
        return response()->json([
            'message' => 'Webhook received',
        ], 202)->withHeaders([
            'X-Pathao-Merchant-Webhook-Integration-Secret' => $token,
        ]);
    }
}
