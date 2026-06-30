<?php

namespace App\Services\Transactions;

use App\DTOs\Transactions\ParsedTransactionData;

class TransactionTextParser
{
    public function __construct(private readonly TransactionParserService $parserService) {}

    public function parse(string $text): ParsedTransactionData
    {
        return $this->parserService->parse($text);
    }
}
