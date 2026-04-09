<?php

namespace App\Repositories\Contracts;

interface PaymentRepositoryInterface
{
    public function upsert(array $data);
    public function findByPaymentId(string $paymentId);
    public function list();
}
