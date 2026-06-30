<?php

namespace App\Services\Transactions;

use App\DTOs\Transactions\ParsedTransactionData;
use App\Enums\TransactionType;
use Carbon\CarbonImmutable;

class TransactionParserService
{
    /** @var array<string, string> */
    private const CATEGORY_ALIASES = [
        'food' => 'food-drink',
        'food-drink' => 'food-drink',
        'makanan' => 'food-drink',
        'minuman' => 'food-drink',
        'transport' => 'transport',
        'transportasi' => 'transport',
        'shopping' => 'shopping',
        'groceries' => 'groceries',
        'bills' => 'bills',
        'subscription' => 'bills',
        'subscriptions' => 'bills',
        'tagihan' => 'bills',
        'entertainment' => 'entertainment',
        'health' => 'health',
        'education' => 'education',
        'family' => 'family',
        'other-expense' => 'other-expense',
    ];

    public function __construct(private readonly AmountParser $amountParser) {}

    public function parse(string $text): ParsedTransactionData
    {
        $rawText = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        $tokens = $rawText === '' ? [] : explode(' ', $rawText);
        $amount = null;
        $amountIndex = null;
        $type = null;
        $categorySlug = null;
        $categoryIndex = null;
        $transactionDate = null;
        $dateIndex = null;

        foreach ($tokens as $index => $token) {
            if ($transactionDate === null && $this->dateFromToken($token)) {
                $transactionDate = $this->dateFromToken($token);
                $dateIndex = $index;
                continue;
            }

            $parsedAmount = $this->amountParser->parseToken($token);

            if ($parsedAmount !== null && $parsedAmount > 0) {
                $amount = $parsedAmount;
                $amountIndex = $index;
                continue;
            }

            $detectedType = $this->detectTypeToken($token);

            if ($detectedType !== null) {
                $type = $detectedType;
                continue;
            }

            $detectedCategory = $this->categorySlugFromToken($token);

            if ($detectedCategory !== null) {
                $categorySlug = $detectedCategory;
                $categoryIndex = $index;
            }
        }

        $descriptionTokens = array_values(array_filter(
            $tokens,
            fn (string $token, int $index): bool => $index !== $amountIndex
                && $index !== $dateIndex
                && $index !== $categoryIndex
                && ! $this->isTypeMarker($token),
            ARRAY_FILTER_USE_BOTH
        ));

        $description = trim(implode(' ', $descriptionTokens));
        $type ??= $this->detectTypeFromDescription($descriptionTokens) ?? TransactionType::Expense;
        $confidence = 40;

        if ($amount !== null) {
            $confidence += 25;
        }

        if ($description !== '') {
            $confidence += 20;
        }

        if ($categorySlug !== null) {
            $confidence += 10;
        }

        if ($transactionDate !== null) {
            $confidence += 5;
        }

        return new ParsedTransactionData(
            rawText: $rawText,
            description: $description !== '' ? $description : null,
            amount: $amount,
            type: $type,
            confidenceScore: min($confidence, 100),
            tokens: $tokens,
            categorySlug: $categorySlug,
            transactionDate: $transactionDate,
        );
    }

    private function dateFromToken(string $token): ?CarbonImmutable
    {
        $normalized = strtolower(trim($token));

        if ($normalized === 'yesterday') {
            return CarbonImmutable::now()->subDay()->startOfDay();
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $normalized)) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $normalized);

        return $date && $date->format('Y-m-d') === $normalized ? $date->startOfDay() : null;
    }

    private function detectTypeToken(string $token): ?TransactionType
    {
        $normalized = strtolower(trim($token));

        if (in_array($normalized, ['income', 'pemasukan', 'masuk'], true)) {
            return TransactionType::Income;
        }

        if (in_array($normalized, ['expense', 'pengeluaran', 'keluar'], true)) {
            return TransactionType::Expense;
        }

        return null;
    }

    /** @param array<int, string> $tokens */
    private function detectTypeFromDescription(array $tokens): ?TransactionType
    {
        foreach ($tokens as $token) {
            $normalized = strtolower($token);

            if (in_array($normalized, ['gaji', 'salary', 'bonus'], true)) {
                return TransactionType::Income;
            }

            if (in_array($normalized, ['bayar', 'beli'], true)) {
                return TransactionType::Expense;
            }
        }

        return null;
    }

    private function isTypeMarker(string $token): bool
    {
        return $this->detectTypeToken($token) !== null;
    }

    private function categorySlugFromToken(string $token): ?string
    {
        $normalized = strtolower(trim($token));

        if ($normalized === '') {
            return null;
        }

        return self::CATEGORY_ALIASES[$normalized] ?? null;
    }
}
