<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentWebhookRequest;
use App\Services\WebhookService;
use Illuminate\Http\JsonResponse;

class WebhookController extends Controller
{
    private WebhookService $service;

    public function __construct(WebhookService $service)
    {
        $this->service = $service;
    }

    public function handle(StorePaymentWebhookRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        
        $this->service->processWebhook($validatedData);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook processed successfully'
        ], 200);
    }
}
