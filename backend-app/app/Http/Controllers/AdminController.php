<?php

namespace App\Http\Controllers;

use App\Services\WebhookService;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    private WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }

    public function refund(string $id): JsonResponse
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        if ($payment->event === 'payment.refunded') {
            return response()->json(['message' => 'Payment is already refunded'], 400);
        }

        // Simulate a webhook payload so it can update the event status to refunded
        $fakeWebhookPayload = [
            'event_id' => 'evt_' . Str::uuid()->toString(),
            'payment_id' => $id,
            'event' => 'payment.refunded',
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'user_id' => $payment->user_id,
            'timestamp' => now()->toDateTimeString(),
        ];

        $this->webhookService->processWebhook($fakeWebhookPayload);

        return response()->json([
            'message' => 'Refund processed successfully',
            'status' => 'payment.refunded'
        ]);
    }
}
