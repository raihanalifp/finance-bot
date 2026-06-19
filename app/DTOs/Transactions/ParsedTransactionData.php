<?php

namespace App\DTOs\Transactions;

use App\Enums\TransactionType;

final readonly class ParsedTransactionData
{
    public function __construct(
        public string $rawText,
        public ?string $description,
        public ?float $amount,
        public ?TransactionType $type,
        public int $confidenceScore,
        public array $tokens = [],
    ) {}

    public function isComplete(): bool
    {
        return filled($this->description) && $this->amount !== null && $this->type !== null;
    }
}
