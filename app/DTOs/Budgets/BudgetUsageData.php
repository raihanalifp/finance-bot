<?php

namespace App\DTOs\Budgets;

use App\Models\MonthlyBudget;

final readonly class BudgetUsageData
{
    public function __construct(
        public MonthlyBudget $budget,
        public float $spentAmount,
        public float $remainingAmount,
        public float $percentage,
        public int $transactionCount,
    ) {}

    public function isWarning(): bool
    {
        return $this->percentage >= 80 && $this->percentage < 100;
    }

    public function isExceeded(): bool
    {
        return $this->percentage >= 100;
    }
}
