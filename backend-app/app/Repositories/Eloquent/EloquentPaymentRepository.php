<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function upsert(array $data)
    {
        return Payment::updateOrCreate(
            ['payment_id' => $data['payment_id']],
            $data
        );
    }

    public function findByPaymentId(string $paymentId)
    {
        return Payment::find($paymentId);
    }

    public function list()
    {
        return Payment::orderBy('updated_at', 'desc')->get();
    }
}
