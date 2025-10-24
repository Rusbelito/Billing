<?php

namespace Rusbelito\Billing\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Rusbelito\Billing\Services\WebhookService;

class WebhookController extends Controller
{
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    /**
     * Handle PaymentsWay webhook
     */
    public function paymentsway(Request $request)
    {
        $payload = $request->all();

        try {
            $result = $this->webhookService->handlePaymentsWayWebhook($payload);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ], $result['status_code']);

        } catch (\Exception $e) {
            \Log::error('Webhook processing error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error processing webhook',
            ], 500);
        }
    }
}