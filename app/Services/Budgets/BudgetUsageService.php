<?php

namespace App\Services\Budgets;

use App\DTOs\Budgets\BudgetUsageData;
use App\Enums\TransactionType;
use App\Models\MonthlyBudget;
use App\Models\Transaction;
use Illuminate\Support\Collection;

class BudgetUsageService
{
    public function usage(MonthlyBudget $budget): BudgetUsageData
    {
        $aggregate = $this->expenseAggregateQuery($budget->user_id, $budget->year, $budget->month)
            ->when($budget->category_id !== null, fn ($query) => $query->where('category_id', $budget->category_id))
            ->first();

        return $this->toUsageData($budget, (float) ($aggregate->spent_amount ?? 0), (int) ($aggregate->transaction_count ?? 0));
    }

    public function activeUsages(int $userId, ?int $year = null, ?int $month = null): Collection
    {
        $year ??= (int) now()->format('Y');
        $month ??= (int) now()->format('m');

        $budgets = MonthlyBudget::query()
            ->with('category')
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->where('year', $year)
            ->where('month', $month)
            ->orderByRaw('category_id is null desc')
            ->get();

        if ($budgets->isEmpty()) {
            return collect();
        }

        $categoryAggregates = $this->expenseAggregateQuery($userId, $year, $month)
            ->select('category_id')
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        $totalAggregate = $this->expenseAggregateQuery($userId, $year, $month)->first();

        return $budgets->map(function (MonthlyBudget $budget) use ($categoryAggregates, $totalAggregate): BudgetUsageData {
            $aggregate = $budget->category_id === null
                ? $totalAggregate
                : $categoryAggregates->get($budget->category_id);

            return $this->toUsageData(
                $budget,
                (float) ($aggregate->spent_amount ?? 0),
                (int) ($aggregate->transaction_count ?? 0),
            );
        });
    }

    private function expenseAggregateQuery(int $userId, int $year, int $month)
    {
        return Transaction::query()
            ->where('user_id', $userId)
            ->where('type', TransactionType::Expense)
            ->whereYear('transaction_date', $year)
            ->whereMonth('transaction_date', $month)
            ->selectRaw('coalesce(sum(amount), 0) as spent_amount')
            ->selectRaw('count(*) as transaction_count');
    }

    private function toUsageData(MonthlyBudget $budget, float $spentAmount, int $transactionCount): BudgetUsageData
    {
        $budgetAmount = max((float) $budget->amount, 0.01);

        return new BudgetUsageData(
            budget: $budget,
            spentAmount: $spentAmount,
            remainingAmount: max(0, (float) $budget->amount - $spentAmount),
            percentage: round(($spentAmount / $budgetAmount) * 100, 2),
            transactionCount: $transactionCount,
        );
    }
}
