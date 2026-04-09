<?php

namespace App\Repositories\Contracts;

interface EventLogRepositoryInterface
{
    public function store(array $data);
    public function findByPaymentId(string $paymentId);
}
