<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    private string $providerBaseUrl;

    public function __construct()
    {
        // Default to PHP built in server on port 8001 if not in env
        $this->providerBaseUrl = env('PAYMENT_PROVIDER_URL', 'http://localhost:8001');
    }

    public function tokenize(array $cardDetails): ?string
    {
        $response = Http::post($this->providerBaseUrl . '/api/tokens', $cardDetails);

        if ($response->successful()) {
            return $response->json('data.token');
        }

        Log::error('Payment tokenize failed', ['response' => $response->body()]);
        return null;
    }

    public function charge(string $token, float $amount, string $currency, string $userId): bool
    {
        // Provide the webhook URL for the provider to call back
        $webhookUrl = url('/api/webhook');

        $response = Http::post($this->providerBaseUrl . '/api/charges', [
            'token' => $token,
            'amount' => $amount,
            'currency' => $currency,
            'user_id' => $userId,
            'webhook_url' => $webhookUrl
        ]);

        if ($response->successful()) {
            return true;
        }

        Log::error('Payment charge failed', ['response' => $response->body()]);
        return false;
    }
}
