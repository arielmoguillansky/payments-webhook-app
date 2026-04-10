<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Contracts\EventLogRepositoryInterface;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    private PaymentRepositoryInterface $paymentRepo;
    private EventLogRepositoryInterface $eventLogRepo;

    public function __construct(
        PaymentRepositoryInterface $paymentRepo,
        EventLogRepositoryInterface $eventLogRepo
    ) {
        $this->paymentRepo = $paymentRepo;
        $this->eventLogRepo = $eventLogRepo;
    }

    public function index(): JsonResponse
    {
        $payments = $this->paymentRepo->list();
        return response()->json([
            'data' => $payments
        ]);
    }

    public function events(string $id): JsonResponse
    {
        $events = $this->eventLogRepo->findByPaymentId($id);
        return response()->json([
            'data' => $events
        ]);
    }
}
