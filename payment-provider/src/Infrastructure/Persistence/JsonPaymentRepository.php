<?php

namespace Provider\Infrastructure\Persistence;

use Provider\Domain\Model\Payment;
use Provider\Domain\Repository\PaymentRepositoryInterface;
use Provider\Domain\Model\CardToken;

class JsonPaymentRepository implements PaymentRepositoryInterface
{
    private string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
        if (!file_exists($this->filePath)) {
            file_put_contents($this->filePath, json_encode([]));
        }
    }

    public function save(Payment $payment): void
    {
        $data = $this->loadAll();
        $data[$payment->getId()] = $payment->toArray();
        file_put_contents($this->filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function findById(string $id): ?Payment
    {
        $data = $this->loadAll();
        if (!isset($data[$id])) {
            return null;
        }

        $p = $data[$id];
        return new Payment(
            $p['id'],
            $p['token_id'],
            $p['amount'],
            $p['currency'],
            $p['webhook_url'],
            $p['status']
        );
    }

    private function loadAll(): array
    {
        $content = file_get_contents($this->filePath);
        return json_decode($content, true) ?: [];
    }
}
