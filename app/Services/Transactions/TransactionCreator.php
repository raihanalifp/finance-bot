<?php

namespace App\Services\Transactions;

use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionSource;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDraft;
use App\Models\TelegramUser;
use App\Enums\AuditAction;
use App\Services\Budgets\BudgetAlertService;
use App\Services\Security\AuditLogService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionCreator
{
    public function __construct(
        private readonly BudgetAlertService $budgetAlertService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function createFromDraft(TransactionDraft $draft, Category $category, TelegramUser $telegramUser): Transaction
    {
        if ($draft->amount === null || blank($draft->description) || $draft->type === null) {
            throw new InvalidArgumentException('Transaction draft is incomplete.');
        }

        $transaction = DB::transaction(function () use ($draft, $category, $telegramUser): Transaction {
            $transaction = Transaction::query()->create([
                'user_id' => $draft->user_id,
                'category_id' => $category->id,
                'telegram_user_id' => $telegramUser->id,
                'type' => $draft->type,
                'amount' => $draft->amount,
                'currency' => $draft->currency,
                'description' => $draft->description,
                'transaction_date' => $draft->transaction_date ?? now()->toDateString(),
                'transaction_time' => $draft->transaction_time ?? now()->format('H:i:s'),
                'source' => TransactionSource::Telegram,
                'raw_text' => $draft->raw_text,
                'metadata' => [
                    'draft_uuid' => $draft->uuid,
                    'confidence_score' => $draft->confidence_score,
                    'parser_result' => $draft->parser_result,
                ],
                'created_by' => $draft->user_id,
            ]);

            $draft->update([
                'category_id' => $category->id,
                'status' => TransactionDraftStatus::Confirmed,
                'confirmed_at' => now(),
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        });

        $this->budgetAlertService->checkAfterTransaction($transaction);
        $this->auditLogService->record(
            AuditAction::Created,
            entity: $transaction,
            newValues: $transaction->only(['uuid', 'user_id', 'category_id', 'type', 'amount', 'currency', 'description', 'transaction_date', 'source']),
            context: ['source' => 'telegram_transaction_creator'],
            userId: $transaction->user_id,
        );

        return $transaction;
    }
}
