<?php

namespace App\Services\Telegram;

use App\Enums\AuditAction;
use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionLogStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\Transaction;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Services\Security\AuditLogService;
use Illuminate\Support\Facades\DB;

class TelegramCommandService
{
    private const UNDO_WINDOW_MINUTES = 15;

    public function __construct(
        private readonly TelegramResponseService $responseService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function handle(string $command, TelegramUser $telegramUser, TransactionLog $log): string
    {
        return match ($command) {
            '/start' => $this->responseService->startHelp(),
            '/help' => $this->responseService->inputHelp(),
            '/today' => $this->today($telegramUser),
            '/month' => $this->month($telegramUser),
            '/last' => $this->last($telegramUser),
            '/undo' => $this->undo($telegramUser, $log),
            '/categories' => $this->categories($telegramUser),
            '/cancel' => $this->cancel($telegramUser),
            default => $this->responseService->unknownCommand(),
        };
    }

    private function today(TelegramUser $telegramUser): string
    {
        $today = now()->toDateString();
        $summary = $this->summary($telegramUser, $today, $today);

        return $this->responseService->summary('Ringkasan hari ini', $summary['income'], $summary['expense'], $summary['count']);
    }

    private function month(TelegramUser $telegramUser): string
    {
        $summary = $this->summary($telegramUser, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());

        return $this->responseService->summary('Ringkasan bulan berjalan', $summary['income'], $summary['expense'], $summary['count']);
    }

    /** @return array{income: float, expense: float, count: int} */
    private function summary(TelegramUser $telegramUser, string $startDate, string $endDate): array
    {
        $rows = Transaction::query()
            ->select('type')
            ->selectRaw('sum(amount) as total')
            ->where('user_id', $telegramUser->user_id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->groupBy('type')
            ->pluck('total', 'type');

        $count = Transaction::query()
            ->where('user_id', $telegramUser->user_id)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->count();

        return [
            'income' => (float) ($rows[TransactionType::Income->value] ?? 0),
            'expense' => (float) ($rows[TransactionType::Expense->value] ?? 0),
            'count' => $count,
        ];
    }

    private function last(TelegramUser $telegramUser): string
    {
        $transaction = $this->lastTelegramTransaction($telegramUser);

        if (! $transaction) {
            return $this->responseService->noLastTransaction();
        }

        return $this->responseService->lastTransaction($transaction);
    }

    private function undo(TelegramUser $telegramUser, TransactionLog $log): string
    {
        $transaction = $this->lastTelegramTransaction($telegramUser, onlyUndoable: true);

        if (! $transaction) {
            return $this->responseService->undoUnavailable();
        }

        $oldValues = $transaction->only(['uuid', 'user_id', 'category_id', 'type', 'amount', 'currency', 'description', 'transaction_date', 'source']);

        DB::transaction(function () use ($transaction, $log): void {
            $transaction->delete();

            $log->update([
                'transaction_id' => $transaction->id,
                'status' => TransactionLogStatus::Processed,
                'processed_at' => now(),
            ]);
        });

        $this->auditLogService->record(
            AuditAction::Deleted,
            entity: $transaction,
            oldValues: $oldValues,
            context: ['source' => 'telegram_undo_command'],
            userId: $telegramUser->user_id,
        );

        return $this->responseService->undoSuccess($transaction);
    }

    private function categories(TelegramUser $telegramUser): string
    {
        $categories = Category::query()
            ->where('user_id', $telegramUser->user_id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->responseService->categories($categories);
    }

    private function cancel(TelegramUser $telegramUser): string
    {
        $draft = TransactionDraft::query()
            ->where('telegram_user_id', $telegramUser->id)
            ->where('status', TransactionDraftStatus::Pending)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();

        if (! $draft) {
            return $this->responseService->nothingToCancel();
        }

        $draft->update(['status' => TransactionDraftStatus::Cancelled]);

        return $this->responseService->cancelled();
    }

    private function lastTelegramTransaction(TelegramUser $telegramUser, bool $onlyUndoable = false): ?Transaction
    {
        return Transaction::query()
            ->with('category')
            ->where('telegram_user_id', $telegramUser->id)
            ->where('source', TransactionSource::Telegram)
            ->when($onlyUndoable, fn ($query) => $query->where('created_at', '>=', now()->subMinutes(self::UNDO_WINDOW_MINUTES)))
            ->latest()
            ->first();
    }
}
