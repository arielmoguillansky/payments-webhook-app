<?php

namespace Provider\Domain\Repository;

use Provider\Domain\Model\Payment;

interface WebhookSenderInterface
{
    public function send(Payment $payment, string $eventId): void;
}
