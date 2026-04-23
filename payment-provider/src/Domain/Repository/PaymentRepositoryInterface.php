<?php

namespace Provider\Domain\Repository;

use Provider\Domain\Model\Payment;

interface PaymentRepositoryInterface
{
    public function save(Payment $payment): void;
    public function findById(string $id): ?Payment;
}
