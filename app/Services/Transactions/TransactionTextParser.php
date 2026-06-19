<?php

namespace App\Services\Transactions;

use App\DTOs\Transactions\ParsedTransactionData;
use App\Enums\TransactionType;

class TransactionTextParser
{
    public function __construct(private readonly AmountParser $amountParser) {}

    public function parse(string $text): ParsedTransactionData
    {
        $rawText = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        $tokens = $rawText === '' ? [] : explode(' ', $rawText);
        $amount = null;
        $amountIndex = null;

        foreach ($tokens as $index => $token) {
            $parsedAmount = $this->amountParser->parseToken($token);

            if ($parsedAmount !== null && $parsedAmount > 0) {
                $amount = $parsedAmount;
                $amountIndex = $index;
            }
        }

        $type = $this->detectType($tokens);
        $descriptionTokens = array_values(array_filter(
            $tokens,
            fn (string $token, int $index): bool => $index !== $amountIndex && ! $this->isTypeToken($token),
            ARRAY_FILTER_USE_BOTH
        ));

        $description = trim(implode(' ', $descriptionTokens));
        $confidence = 40;

        if ($amount !== null) {
            $confidence += 30;
        }

        if ($description !== '') {
            $confidence += 20;
        }

        if ($type !== null) {
            $confidence += 10;
        }

        return new ParsedTransactionData(
            rawText: $rawText,
            description: $description !== '' ? $description : null,
            amount: $amount,
            type: $type ?? TransactionType::Expense,
            confidenceScore: min($confidence, 100),
            tokens: $tokens,
        );
    }

    private function detectType(array $tokens): ?TransactionType
    {
        foreach ($tokens as $token) {
            $normalized = strtolower($token);

            if (in_array($normalized, ['income', 'pemasukan', 'masuk', 'gaji', 'salary', 'bonus'], true)) {
                return TransactionType::Income;
            }

            if (in_array($normalized, ['expense', 'pengeluaran', 'keluar', 'bayar', 'beli'], true)) {
                return TransactionType::Expense;
            }
        }

        return null;
    }

    private function isTypeToken(string $token): bool
    {
        return in_array(strtolower($token), [
            'income', 'pemasukan', 'masuk', 'salary', 'expense', 'pengeluaran', 'keluar',
        ], true);
    }
}
