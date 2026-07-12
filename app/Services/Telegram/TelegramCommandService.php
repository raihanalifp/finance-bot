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
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class TelegramCommandService
{
    private const UNDO_WINDOW_MINUTES = 15;

    public function __construct(
        private readonly TelegramResponseService $responseService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function handle(string $command, string $arguments, TelegramUser $telegramUser, TransactionLog $log): string
    {
        return match ($command) {
            '/start' => $this->responseService->startHelp(),
            '/help' => $this->responseService->commandsHelp(),
            '/format' => $this->responseService->inputHelp(),
            '/today' => $this->today($telegramUser),
            '/month' => $this->month($telegramUser, $arguments),
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

    private function month(TelegramUser $telegramUser, string $arguments = ''): string
    {
        $range = $this->monthRange($arguments);

        if (isset($range['error'])) {
            return $this->responseService->dateRangeError($range['error']);
        }

        $startDate = $range['start_date'];
        $endDate = $range['end_date'];
        $summary = $this->summary($telegramUser, $startDate, $endDate);
        $breakdown = $this->categoryBreakdown($telegramUser, $startDate, $endDate);
        $title = $arguments === '' ? 'Ringkasan bulan berjalan' : 'Ringkasan periode';

        return $this->responseService->detailedSummary(
            $title,
            $startDate,
            $endDate,
            $summary['income'],
            $summary['expense'],
            $summary['count'],
            $breakdown,
        );
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

    /** @return array{start_date: string, end_date: string}|array{error: string} */
    private function monthRange(string $arguments): array
    {
        if ($arguments === '') {
            return [
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
            ];
        }

        $keys = $this->parseKeyedArguments($arguments);
        $startDate = $keys['from'] ?? $keys['start'] ?? $keys['dari'] ?? $keys['mulai'] ?? null;
        $endDate = $keys['to'] ?? $keys['end'] ?? $keys['sampai'] ?? $keys['hingga'] ?? null;

        if (! $startDate || ! $endDate) {
            $dates = $this->dateTokens($arguments);
            $startDate ??= $dates[0] ?? null;
            $endDate ??= $dates[1] ?? null;
        }

        if (! $startDate || ! $endDate) {
            return ['error' => 'Format periode belum lengkap.'];
        }

        $start = $this->parseDate($startDate);
        $end = $this->parseDate($endDate);

        if (! $start || ! $end) {
            return ['error' => 'Format tanggal harus YYYY-MM-DD.'];
        }

        if ($end->lt($start)) {
            return ['error' => 'Tanggal akhir tidak boleh lebih awal dari tanggal awal.'];
        }

        return [
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
        ];
    }

    /** @return array<string, string> */
    private function parseKeyedArguments(string $arguments): array
    {
        $keys = [];

        foreach (preg_split('/\s+/', trim($arguments)) ?: [] as $token) {
            if (! str_contains($token, '=')) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $token, 2));

            if ($key !== '' && $value !== '') {
                $keys[strtolower($key)] = $value;
            }
        }

        return $keys;
    }

    /** @return array<int, string> */
    private function dateTokens(string $arguments): array
    {
        preg_match_all('/\b\d{4}-\d{2}-\d{2}\b/', $arguments, $matches);

        return $matches[0] ?? [];
    }

    private function parseDate(string $date): ?CarbonImmutable
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return null;
        }

        try {
            $parsed = CarbonImmutable::createFromFormat('Y-m-d', $date);
        } catch (\Throwable) {
            return null;
        }

        return $parsed && $parsed->format('Y-m-d') === $date ? $parsed->startOfDay() : null;
    }

    /** @return array<string, array<int, array{name: string, total: float, count: int}>> */
    private function categoryBreakdown(TelegramUser $telegramUser, string $startDate, string $endDate): array
    {
        $rows = Transaction::query()
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.user_id', $telegramUser->user_id)
            ->whereBetween('transactions.transaction_date', [$startDate, $endDate])
            ->groupBy('transactions.type', 'transactions.category_id', 'categories.name')
            ->orderBy('transactions.type')
            ->orderByDesc(DB::raw('sum(transactions.amount)'))
            ->get([
                'transactions.type',
                DB::raw('coalesce(categories.name, "Uncategorized") as name'),
                DB::raw('sum(transactions.amount) as total'),
                DB::raw('count(transactions.id) as transaction_count'),
            ]);

        $breakdown = [
            TransactionType::Income->value => [],
            TransactionType::Expense->value => [],
        ];

        foreach ($rows as $row) {
            $type = $row->type instanceof TransactionType ? $row->type->value : (string) $row->type;

            if (! array_key_exists($type, $breakdown)) {
                continue;
            }

            $breakdown[$type][] = [
                'name' => (string) $row->name,
                'total' => (float) $row->total,
                'count' => (int) $row->transaction_count,
            ];
        }

        return $breakdown;
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
