<?php

namespace App\Services\Budgets;

use App\DTOs\Budgets\BudgetUsageData;
use App\Enums\AuditAction;
use App\Enums\BudgetAlertThreshold;
use App\Enums\TransactionType;
use App\Models\BudgetAlert;
use App\Models\MonthlyBudget;
use App\Models\Transaction;
use App\Models\TelegramUser;
use App\Services\Telegram\TelegramApiClient;
use App\Services\Security\AuditLogService;
use Illuminate\Support\Number;

class BudgetAlertService
{
    public function __construct(
        private readonly BudgetUsageService $budgetUsageService,
        private readonly TelegramApiClient $telegramApiClient,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function checkAfterTransaction(Transaction $transaction): void
    {
        if ($transaction->type !== TransactionType::Expense) {
            return;
        }

        $budgets = MonthlyBudget::query()
            ->with('category')
            ->where('user_id', $transaction->user_id)
            ->where('is_active', true)
            ->where('year', (int) $transaction->transaction_date->format('Y'))
            ->where('month', (int) $transaction->transaction_date->format('m'))
            ->where(function ($query) use ($transaction): void {
                $query->whereNull('category_id')->orWhere('category_id', $transaction->category_id);
            })
            ->get();

        foreach ($budgets as $budget) {
            $this->sendAlertIfNeeded($this->budgetUsageService->usage($budget));
        }
    }

    public function sendAlertIfNeeded(BudgetUsageData $usage): void
    {
        $threshold = $this->thresholdFor($usage);

        if (! $threshold) {
            return;
        }

        $alert = BudgetAlert::query()->firstOrCreate(
            [
                'monthly_budget_id' => $usage->budget->id,
                'year' => $usage->budget->year,
                'month' => $usage->budget->month,
                'threshold' => $threshold->value,
            ],
            [
                'user_id' => $usage->budget->user_id,
                'category_id' => $usage->budget->category_id,
                'budget_amount' => $usage->budget->amount,
                'spent_amount' => $usage->spentAmount,
                'percentage' => $usage->percentage,
                'metadata' => ['source' => 'budget_alert_service'],
            ]
        );

        if ($alert->wasRecentlyCreated) {
            $message = $this->message($usage, $threshold);

            TelegramUser::query()
                ->where('user_id', $usage->budget->user_id)
                ->where('is_authorized', true)
                ->get()
                ->each(fn (TelegramUser $telegramUser) => $this->telegramApiClient->sendMessage($telegramUser->telegram_chat_id, $message));

            $alert->update(['sent_at' => now()]);
            $this->auditLogService->record(
                AuditAction::BudgetAlertSent,
                entity: $alert,
                newValues: $alert->only(['monthly_budget_id', 'threshold', 'budget_amount', 'spent_amount', 'percentage', 'sent_at']),
                context: ['category_name' => $usage->budget->category?->name],
                userId: $usage->budget->user_id,
            );
        }
    }

    private function thresholdFor(BudgetUsageData $usage): ?BudgetAlertThreshold
    {
        if ($usage->percentage >= BudgetAlertThreshold::Exceeded100->value) {
            return BudgetAlertThreshold::Exceeded100;
        }

        if ($usage->percentage >= BudgetAlertThreshold::Warning80->value) {
            return BudgetAlertThreshold::Warning80;
        }

        return null;
    }

    private function message(BudgetUsageData $usage, BudgetAlertThreshold $threshold): string
    {
        $categoryName = $usage->budget->category?->name ?? 'Total Budget';
        $spent = Number::currency($usage->spentAmount, $usage->budget->currency, 'id');
        $budget = Number::currency((float) $usage->budget->amount, $usage->budget->currency, 'id');
        $remaining = Number::currency($usage->remainingAmount, $usage->budget->currency, 'id');

        if ($threshold === BudgetAlertThreshold::Exceeded100) {
            return "🚨 Budget terlampaui\n\nKategori: {$categoryName}\nTerpakai: {$spent} / {$budget}\nProgress: {$usage->percentage}%\nSisa: {$remaining}\n\nPertimbangkan menahan pengeluaran kategori ini.";
        }

        return "⚠️ Budget hampir habis\n\nKategori: {$categoryName}\nTerpakai: {$spent} / {$budget}\nProgress: {$usage->percentage}%\nSisa: {$remaining}\n\nSudah melewati 80% dari budget bulanan.";
    }
}
