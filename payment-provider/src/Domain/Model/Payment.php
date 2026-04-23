<?php

namespace Provider\Domain\Model;

class Payment
{
    private string $id;
    private string $tokenId;
    private float $amount;
    private string $currency;
    private string $status; // pending, success, failed, refunded
    private ?string $webhookUrl;

    public function __construct(
        string $id,
        string $tokenId,
        float $amount,
        string $currency,
        string $webhookUrl = null,
        string $status = 'pending'
    ) {
        $this->id = $id;
        $this->tokenId = $tokenId;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->webhookUrl = $webhookUrl;
    }

    public static function initiate(CardToken $token, float $amount, string $currency, string $webhookUrl): self
    {
        return new self(
            'pay_' . bin2hex(random_bytes(10)),
            $token->getValue(),
            $amount,
            $currency,
            $webhookUrl,
            'pending'
        );
    }

    public function process(): void
    {
        // Simulate a success charge rate
        if (rand(1, 100) > 10) {
            $this->status = 'success';
        } else {
            $this->status = 'failed';
        }
    }

    public function refund(): void
    {
        if ($this->status !== 'success') {
            throw new \DomainException('Only successful payments can be refunded');
        }
        $this->status = 'refunded';
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getWebhookUrl(): ?string
    {
        return $this->webhookUrl;
    }

    public function getTokenId(): string
    {
        return $this->tokenId;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'token_id' => $this->tokenId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'webhook_url' => $this->webhookUrl,
        ];
    }
}
