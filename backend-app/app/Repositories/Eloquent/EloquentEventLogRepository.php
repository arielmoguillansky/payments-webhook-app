<?php

namespace App\Repositories\Eloquent;

use App\Models\EventLog;
use App\Repositories\Contracts\EventLogRepositoryInterface;

class EloquentEventLogRepository implements EventLogRepositoryInterface
{
    public function store(array $data)
    {
        return EventLog::create($data);
    }

    public function findByPaymentId(string $paymentId)
    {
        return EventLog::where('payment_id', $paymentId)
            ->orderBy('timestamp', 'asc')
            ->get();
    }
}
