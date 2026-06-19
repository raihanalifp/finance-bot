<?php

namespace App\DTOs\Reports;

final readonly class ReportSummaryData
{
    public function __construct(
        public float $incomeTotal,
        public float $expenseTotal,
        public float $netTotal,
        public float $averageDailyExpense,
        public float $averageMonthlyExpense,
        public int $transactionCount,
        public ?array $largestCategory,
        public string $expenseTrend,
    ) {}
}
