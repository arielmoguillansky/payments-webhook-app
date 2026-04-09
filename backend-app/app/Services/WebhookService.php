<?php

namespace App\Services;

use App\Repositories\Contracts\EventLogRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    private EventLogRepositoryInterface $eventLogRepo;
    private PaymentRepositoryInterface $paymentRepo;

    public function __construct(
        EventLogRepositoryInterface $eventLogRepo,
        PaymentRepositoryInterface $paymentRepo
    ) {
        $this->eventLogRepo = $eventLogRepo;
        $this->paymentRepo = $paymentRepo;
    }

    /**
     * Process an incoming webhook idempotently.
     */
    public function processWebhook(array $data)
    {
        try {
            // We ALWAYS attempt to store the EventLog first.
            // If the event_id already exists, the database unique constraint 
            // will throw an exception, protecting us from processing duplicates.
            $this->eventLogRepo->store($data);
        } catch (QueryException $e) {
            // Code 23000 / 23505 usually indicates a unique constraint violation
            if ($e->getCode() == 23000 || $e->getCode() == 23505 || str_contains($e->getMessage(), 'UNIQUE')) {
                Log::info("Idempotency match: Webhook event_id already processed.", [
                    'event_id' => $data['event_id'],
                    'payment_id' => $data['payment_id']
                ]);
                return; // Exit early, do not upsert payment
            }
            throw $e;
        }

        Log::info("Processing new Webhook event.", [
            'event_id' => $data['event_id'],
            'payment_id' => $data['payment_id']
        ]);

        // If we reach here, it means the event_id is new.
        // We now idempotently upsert the Current Payment state.
        $this->paymentRepo->upsert([
            'payment_id' => $data['payment_id'],
            'event' => $data['event'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'user_id' => $data['user_id'],
            'last_event_id' => $data['event_id'],
        ]);
    }
}
