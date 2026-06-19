<?php

namespace App\Services\Dashboard;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardDataService
{
    public function overview(): array
    {
        $periodStart = now()->startOfMonth()->toDateString();
        $periodEnd = now()->endOfMonth()->toDateString();
        $summary = $this->monthlySummary($periodStart, $periodEnd);
        $runningBalance = $this->runningBalance();
        $savingsRate = $summary['income'] > 0
            ? round((($summary['income'] - $summary['expense']) / $summary['income']) * 100)
            : 0;

        return [
            'incomeTotal' => $summary['income'],
            'expenseTotal' => $summary['expense'],
            'runningBalance' => $runningBalance,
            'savingsRate' => $savingsRate,
            'cashflow' => $this->cashflowData(),
            'topCategories' => $this->topCategories($periodStart, $periodEnd),
            'recentTransactions' => $this->recentTransactions(),
        ];
    }

    public function transactions(): array
    {
        return [
            'transactions' => Transaction::query()->with('category')->where('user_id', auth()->id())->latest('transaction_date')->paginate(12),
            'sampleTransactions' => [],
        ];
    }

    public function categories(): Collection
    {
        return Category::query()->where('user_id', auth()->id())->orderBy('type')->orderBy('sort_order')->get();
    }

    public function expenseCategories(): Collection
    {
        return Category::query()
            ->where('user_id', auth()->id())
            ->where('type', TransactionType::Expense)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function settings(): Collection
    {
        return Setting::query()->where('user_id', auth()->id())->orderBy('group')->orderBy('key')->get();
    }

    private function monthlySummary(string $periodStart, string $periodEnd): array
    {
        $rows = Transaction::query()
            ->select('type')
            ->selectRaw('sum(amount) as total')
            ->where('user_id', auth()->id())
            ->whereBetween('transaction_date', [$periodStart, $periodEnd])
            ->groupBy('type')
            ->pluck('total', 'type');

        return [
            'income' => (float) ($rows[TransactionType::Income->value] ?? 0),
            'expense' => (float) ($rows[TransactionType::Expense->value] ?? 0),
        ];
    }

    private function runningBalance(): float
    {
        return (float) Transaction::query()
            ->selectRaw("sum(case when type = 'income' then amount when type = 'expense' then -amount else 0 end) as balance")
            ->where('user_id', auth()->id())
            ->value('balance');
    }

    private function recentTransactions(): Collection
    {
        return Transaction::query()
            ->with('category')
            ->where('user_id', auth()->id())
            ->latest('transaction_date')
            ->latest('created_at')
            ->limit(8)
            ->get();
    }

    private function cashflowData(): array
    {
        $rows = Transaction::query()
            ->select('transaction_date')
            ->selectRaw("sum(case when type = 'income' then amount else 0 end) as income")
            ->selectRaw("sum(case when type = 'expense' then amount else 0 end) as expense")
            ->where('user_id', auth()->id())
            ->where('transaction_date', '>=', now()->subDays(13)->toDateString())
            ->groupBy('transaction_date')
            ->orderBy('transaction_date')
            ->get()
            ->keyBy(fn (Transaction $transaction): string => $transaction->transaction_date->toDateString());

        $labels = [];
        $income = [];
        $expense = [];

        for ($day = 13; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $key = $date->toDateString();
            $labels[] = $date->format('d M');
            $income[] = (float) ($rows[$key]->income ?? 0);
            $expense[] = (float) ($rows[$key]->expense ?? 0);
        }

        return compact('labels', 'income', 'expense');
    }

    private function topCategories(string $start, string $end): array
    {
        $rows = Transaction::query()
            ->join('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.user_id', auth()->id())
            ->where('transactions.type', TransactionType::Expense)
            ->whereBetween('transactions.transaction_date', [$start, $end])
            ->groupBy('categories.id', 'categories.name', 'categories.color')
            ->orderByDesc(DB::raw('sum(transactions.amount)'))
            ->limit(5)
            ->get(['categories.name', 'categories.color', DB::raw('sum(transactions.amount) as total')]);

        return $rows->map(fn ($row): array => [
            'name' => $row->name,
            'color' => $row->color ?? '#64748b',
            'total' => (float) $row->total,
        ])->all();
    }
}
