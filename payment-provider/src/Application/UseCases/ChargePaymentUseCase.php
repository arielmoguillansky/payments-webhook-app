<?php

namespace Provider\Application\UseCases;

use Provider\Domain\Model\CardToken;
use Provider\Domain\Model\Payment;
use Provider\Domain\Repository\PaymentRepositoryInterface;
use Provider\Domain\Repository\WebhookSenderInterface;

class ChargePaymentUseCase
{
    private PaymentRepositoryInterface $paymentRepository;
    private WebhookSenderInterface $webhookSender;

    public function __construct(
        PaymentRepositoryInterface $paymentRepository,
        WebhookSenderInterface $webhookSender
    ) {
        $this->paymentRepository = $paymentRepository;
        $this->webhookSender = $webhookSender;
    }

    public function execute(string $tokenString, float $amount, string $currency, string $userId, string $webhookUrl): Payment
    {
        $token = CardToken::fromString($tokenString);

        // Initiate payment aggregate
        $payment = Payment::initiate($token, $amount, $currency, $webhookUrl);
        $this->paymentRepository->save($payment);

        // Simulate bank process
        $payment->process();
        $this->paymentRepository->save($payment);

        // Dispatch Webhook
        if ($payment->getWebhookUrl()) {
            $eventId = 'evt_' . bin2hex(random_bytes(8));
            $this->webhookSender->send($payment, $eventId);
        }

        return $payment;
    }
}
