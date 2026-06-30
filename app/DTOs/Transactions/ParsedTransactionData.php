<?php

namespace App\DTOs\Transactions;

use App\Enums\TransactionType;
use Carbon\CarbonImmutable;

final readonly class ParsedTransactionData
{
    public function __construct(
        public string $rawText,
        public ?string $description,
        public ?float $amount,
        public ?TransactionType $type,
        public int $confidenceScore,
        public array $tokens = [],
        public ?string $categorySlug = null,
        public ?CarbonImmutable $transactionDate = null,
        public array $errors = [],
        public ?string $clarification = null,
    ) {}

    public function isComplete(): bool
    {
        return filled($this->description) && $this->amount !== null && $this->type !== null && $this->errors === [];
    }
}
