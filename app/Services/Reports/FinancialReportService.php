<?php

namespace App\Services\Reports;

use App\DTOs\Reports\ReportFilterData;
use App\DTOs\Reports\ReportSummaryData;
use App\Enums\ReportPeriod;
use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    public function build(ReportFilterData $filter): array
    {
        $summaryRows = $this->summaryRows($filter);
        $categoryBreakdown = $this->categoryBreakdown($filter);
        $trend = $this->trend($filter);
        $incomeTotal = (float) ($summaryRows[TransactionType::Income->value] ?? 0);
        $expenseTotal = (float) ($summaryRows[TransactionType::Expense->value] ?? 0);
        $transactionCount = $this->transactionCount($filter);
        $largestCategory = $categoryBreakdown->first();

        return [
            'filter' => $filter,
            'summary' => new ReportSummaryData(
                incomeTotal: $incomeTotal,
                expenseTotal: $expenseTotal,
                netTotal: $incomeTotal - $expenseTotal,
                averageDailyExpense: $this->averageDailyExpense($filter, $expenseTotal),
                averageMonthlyExpense: $this->averageMonthlyExpense($filter, $expenseTotal),
                transactionCount: $transactionCount,
                largestCategory: $largestCategory,
                expenseTrend: $this->expenseTrend($trend),
            ),
            'lineChart' => $this->lineChart($trend),
            'barChart' => $this->barChart($categoryBreakdown),
            'pieChart' => $this->pieChart($categoryBreakdown),
            'categoryBreakdown' => $categoryBreakdown,
            'trendRows' => $trend,
        ];
    }

    private function summaryRows(ReportFilterData $filter): Collection
    {
        return Transaction::query()
            ->select('type')
            ->selectRaw('sum(amount) as total')
            ->where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$filter->startDate->toDateString(), $filter->endDate->toDateString()])
            ->groupBy('type')
            ->pluck('total', 'type');
    }

    private function transactionCount(ReportFilterData $filter): int
    {
        return Transaction::query()
            ->where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$filter->startDate->toDateString(), $filter->endDate->toDateString()])
            ->count();
    }

    private function categoryBreakdown(ReportFilterData $filter): Collection
    {
        return Transaction::query()
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.user_id', auth()->id())
            ->where('transactions.type', TransactionType::Expense)
            ->whereBetween('transactions.transaction_date', [$filter->startDate->toDateString(), $filter->endDate->toDateString()])
            ->groupBy('transactions.category_id', 'categories.name', 'categories.color')
            ->orderByDesc(DB::raw('sum(transactions.amount)'))
            ->limit(12)
            ->get([
                DB::raw('coalesce(categories.name, "Uncategorized") as name'),
                DB::raw('coalesce(categories.color, "#64748b") as color'),
                DB::raw('sum(transactions.amount) as total'),
                DB::raw('count(transactions.id) as transaction_count'),
            ])
            ->map(fn ($row): array => [
                'name' => $row->name,
                'color' => $row->color,
                'total' => (float) $row->total,
                'transaction_count' => (int) $row->transaction_count,
            ]);
    }

    private function trend(ReportFilterData $filter): Collection
    {
        $bucketExpression = $this->bucketExpression($filter->period);

        return Transaction::query()
            ->selectRaw("{$bucketExpression} as bucket")
            ->selectRaw("sum(case when type = 'income' then amount else 0 end) as income")
            ->selectRaw("sum(case when type = 'expense' then amount else 0 end) as expense")
            ->selectRaw('count(*) as transaction_count')
            ->where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$filter->startDate->toDateString(), $filter->endDate->toDateString()])
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row): array => [
                'bucket' => (string) $row->bucket,
                'income' => (float) $row->income,
                'expense' => (float) $row->expense,
                'net' => (float) $row->income - (float) $row->expense,
                'transaction_count' => (int) $row->transaction_count,
            ]);
    }

    private function bucketExpression(ReportPeriod $period): string
    {
        return match ($period) {
            ReportPeriod::Daily => "date_format(transaction_date, '%H:00')",
            ReportPeriod::Weekly, ReportPeriod::Monthly => "date_format(transaction_date, '%Y-%m-%d')",
            ReportPeriod::Yearly => "date_format(transaction_date, '%Y-%m')",
        };
    }

    private function averageDailyExpense(ReportFilterData $filter, float $expenseTotal): float
    {
        $days = max(1, $filter->startDate->diffInDays($filter->endDate) + 1);

        return $expenseTotal / $days;
    }

    private function averageMonthlyExpense(ReportFilterData $filter, float $expenseTotal): float
    {
        $months = max(1, (($filter->endDate->year - $filter->startDate->year) * 12) + $filter->endDate->month - $filter->startDate->month + 1);

        return $expenseTotal / $months;
    }

    private function expenseTrend(Collection $trend): string
    {
        if ($trend->count() < 2) {
            return 'Data belum cukup';
        }

        $midpoint = (int) floor($trend->count() / 2);
        $firstHalf = (float) $trend->take($midpoint)->sum('expense');
        $secondHalf = (float) $trend->skip($midpoint)->sum('expense');

        if ($secondHalf > $firstHalf * 1.1) {
            return 'Naik';
        }

        if ($secondHalf < $firstHalf * 0.9) {
            return 'Turun';
        }

        return 'Stabil';
    }

    private function lineChart(Collection $trend): array
    {
        return [
            'labels' => $trend->pluck('bucket')->all(),
            'income' => $trend->pluck('income')->all(),
            'expense' => $trend->pluck('expense')->all(),
            'net' => $trend->pluck('net')->all(),
        ];
    }

    private function barChart(Collection $categories): array
    {
        return [
            'labels' => $categories->pluck('name')->all(),
            'values' => $categories->pluck('total')->all(),
            'colors' => $categories->pluck('color')->all(),
        ];
    }

    private function pieChart(Collection $categories): array
    {
        return $this->barChart($categories);
    }
}
