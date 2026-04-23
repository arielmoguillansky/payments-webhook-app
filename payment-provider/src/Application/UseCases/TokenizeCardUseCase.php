<?php

namespace Provider\Application\UseCases;

use Provider\Domain\Model\CardToken;

class TokenizeCardUseCase
{
    public function execute(string $cardNumber, string $expMonth, string $expYear, string $cvc): CardToken
    {
        // In a real scenario, this would validate the card with a PCI-compliant vault or a bank.
        // For DDD learning purposes, we ignore deep validation and generate a token object.

        if (strlen($cardNumber) < 13) {
            throw new \InvalidArgumentException("Invalid card length");
        }

        return CardToken::create();
    }
}
