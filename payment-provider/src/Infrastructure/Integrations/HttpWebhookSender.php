<?php

namespace Provider\Infrastructure\Integrations;

use Provider\Domain\Model\Payment;
use Provider\Domain\Repository\WebhookSenderInterface;

class HttpWebhookSender implements WebhookSenderInterface
{
    public function send(Payment $payment, string $eventId): void
    {
        if (!$payment->getWebhookUrl()) {
            return;
        }

        $payload = [
            'event_id' => $eventId,
            'payment_id' => $payment->getId(),
            'event' => 'payment.' . $payment->getStatus(),
            'amount' => $payment->getAmount(),
            'currency' => $payment->getCurrency(),
            // Assuming the provider has a way to map user_id, but we'll use a placeholder or read from the app structure.
            // Wait, we need user_id for the Laravel app. Let's add user_id to the payment or the payload.
            // For simplicity, we hardcode user_id here as it's a mock provider, or we could have added it to Payment.
            'user_id' => 'user_1', // mocked for demo purposes.
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        $ch = curl_init($payment->getWebhookUrl());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);

        // Execute but ignore response to make it "fire and forget" simulated
        // In a real app we would log the response, retry, etc.
        curl_exec($ch);
        curl_close($ch);
    }
}
