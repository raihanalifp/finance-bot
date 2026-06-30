<?php

namespace App\DTOs\Transactions;

final readonly class TransactionValidationResult
{
    public function __construct(
        public bool $valid,
        public ?string $errorCode = null,
        public ?string $message = null,
    ) {}

    public static function ok(): self
    {
        return new self(valid: true);
    }

    public static function failed(string $errorCode, string $message): self
    {
        return new self(valid: false, errorCode: $errorCode, message: $message);
    }
}
