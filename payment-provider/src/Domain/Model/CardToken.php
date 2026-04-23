<?php

namespace Provider\Domain\Model;

class CardToken
{
    private string $value;

    private function __construct(string $value)
    {
        $this->value = $value;
    }

    public static function create(): self
    {
        // Simple random token generator for the sake of example
        return new self('tok_' . bin2hex(random_bytes(16)));
    }

    public static function fromString(string $value): self
    {
        if (!str_starts_with($value, 'tok_')) {
            throw new \InvalidArgumentException('Invalid token format');
        }
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
