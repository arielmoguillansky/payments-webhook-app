<?php

namespace Provider\Infrastructure\Http\Controllers;

use Provider\Application\UseCases\TokenizeCardUseCase;

class TokenController
{
    private TokenizeCardUseCase $useCase;

    public function __construct(TokenizeCardUseCase $useCase)
    {
        $this->useCase = $useCase;
    }

    public function handle(array $requestData): void
    {
        try {
            $cardNumber = $requestData['card_number'] ?? '';
            $expMonth = $requestData['exp_month'] ?? '';
            $expYear = $requestData['exp_year'] ?? '';
            $cvc = $requestData['cvc'] ?? '';

            $token = $this->useCase->execute($cardNumber, $expMonth, $expYear, $cvc);

            echo json_encode([
                'status' => 'success',
                'data' => [
                    'token' => $token->getValue()
                ]
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
