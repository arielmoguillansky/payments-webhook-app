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

    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Payment::query();

        if (!empty($filters['event'])) {
            $query->where('event', $filters['event']);
        }
        
        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('updated_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('updated_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('updated_at', 'desc')->paginate($perPage);
    }
}
