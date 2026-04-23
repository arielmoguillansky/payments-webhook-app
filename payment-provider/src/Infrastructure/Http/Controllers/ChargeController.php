<?php

namespace Provider\Infrastructure\Http\Controllers;

use Provider\Application\UseCases\ChargePaymentUseCase;

class ChargeController
{
    private ChargePaymentUseCase $useCase;

    public function __construct(ChargePaymentUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function handle(array $requestData): void
    {
        try {
            $token = $requestData['token'] ?? '';
            $amount = (float)($requestData['amount'] ?? 0);
            $currency = $requestData['currency'] ?? 'USD';
            $userId = $requestData['user_id'] ?? 'user_1';
            $webhookUrl = $requestData['webhook_url'] ?? '';

            $payment = $this->useCase->execute($token, $amount, $currency, $userId, $webhookUrl);

            echo json_encode([
                'status' => 'success',
                'message' => 'Payment initiated',
                'data' => $payment->toArray()
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
