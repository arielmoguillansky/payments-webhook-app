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

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $filters = $request->only(['event', 'currency', 'user_id', 'date_from', 'date_to']);
        $perPage = $request->input('per_page', 15);

        $payments = $this->paymentRepo->list($filters, (int) $perPage);

        return response()->json($payments);
    }

    public function events(string $id): JsonResponse
    {
        $events = $this->eventLogRepo->findByPaymentId($id);
        return response()->json([
            'data' => $events
        ]);
    }
}
