<?php

namespace App\Services\Telegram;

use App\DTOs\Telegram\IncomingTelegramMessageData;
use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionLogStatus;
use App\Models\Category;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Models\TelegramUser;
use App\Services\Categories\CategoryMemoryService;
use App\Services\Transactions\TransactionCategoryResolver;
use App\Services\Transactions\TransactionCreator;
use App\Services\Transactions\TransactionTextParser;
use Throwable;

class TelegramTransactionConversationService
{
    public function __construct(
        private readonly TransactionTextParser $parser,
        private readonly TransactionCategoryResolver $categoryResolver,
        private readonly TransactionCreator $transactionCreator,
        private readonly CategoryMemoryService $categoryMemoryService,
        private readonly TelegramMessageFormatter $messageFormatter,
    ) {}

    public function handle(IncomingTelegramMessageData $message, TelegramUser $telegramUser, TransactionLog $log): string
    {
        $text = trim($message->text);

        if (str_starts_with($text, '/')) {
            return $this->handleCommand($text, $telegramUser);
        }

        $pendingDraft = $this->pendingDraft($telegramUser);

        if ($pendingDraft && preg_match('/^[1-5]$/', $text)) {
            return $this->confirmCategory((int) $text, $pendingDraft, $telegramUser, $log);
        }

        if ($pendingDraft) {
            $pendingDraft->update(['status' => TransactionDraftStatus::Cancelled]);
        }

        return $this->createDraftFromText($text, $telegramUser, $log);
    }

    private function handleCommand(string $text, TelegramUser $telegramUser): string
    {
        return match (strtolower(strtok($text, ' '))) {
            '/start' => $this->messageFormatter->startHelp(),
            '/help' => $this->messageFormatter->inputHelp(),
            '/cancel' => $this->cancelPendingDraft($telegramUser),
            default => 'Command belum dikenal. Ketik /help untuk bantuan.',
        };
    }

    private function createDraftFromText(string $text, TelegramUser $telegramUser, TransactionLog $log): string
    {
        $parsed = $this->parser->parse($text);

        if (! $parsed->isComplete()) {
            $log->update([
                'status' => TransactionLogStatus::Failed,
                'error_code' => 'invalid_transaction_input',
                'error_message' => 'Nominal atau deskripsi tidak ditemukan.',
                'processed_at' => now(),
            ]);

            return $this->messageFormatter->incompleteInput();
        }

        $categoryResolution = $this->categoryResolver->resolve($telegramUser->user, $parsed);
        $category = $categoryResolution->category;

        $draft = TransactionDraft::query()->create([
            'user_id' => $telegramUser->user_id,
            'telegram_user_id' => $telegramUser->id,
            'category_id' => $category?->id,
            'type' => $parsed->type,
            'amount' => $parsed->amount,
            'currency' => 'IDR',
            'description' => $parsed->description,
            'transaction_date' => now()->toDateString(),
            'transaction_time' => now()->format('H:i:s'),
            'raw_text' => $parsed->rawText,
            'confidence_score' => $parsed->confidenceScore,
            'parser_result' => [
                'tokens' => $parsed->tokens,
                'category_resolved' => $category !== null,
                'category_strategy' => $categoryResolution->strategy,
                'category_confidence_score' => $categoryResolution->confidenceScore,
                'category_reason' => $categoryResolution->reason,
                'category_memory_id' => $categoryResolution->memory?->id,
            ],
            'status' => TransactionDraftStatus::Pending,
            'expires_at' => now()->addMinutes(10),
        ]);

        $log->update([
            'transaction_draft_id' => $draft->id,
            'status' => TransactionLogStatus::Parsed,
            'processed_at' => now(),
        ]);

        if ($category && ! $categoryResolution->requiresConfirmation) {
            return $this->storeDraft($draft, $category, $telegramUser, $log, $categoryResolution->reason, false);
        }

        return $this->categoryQuestion($telegramUser, $categoryResolution->reason, $categoryResolution->confidenceScore);
    }

    private function confirmCategory(int $choice, TransactionDraft $draft, TelegramUser $telegramUser, TransactionLog $log): string
    {
        $categories = $this->categoryResolver->choiceCategories($telegramUser->user);
        $category = $categories[$choice - 1] ?? null;

        if (! $category) {
            return $this->categoryQuestion($telegramUser);
        }

        $memory = $this->categoryMemoryService->learn(
            $telegramUser->user,
            $category,
            (string) $draft->description,
            [
                'learned_from' => 'telegram_manual_confirmation',
                'draft_uuid' => $draft->uuid,
            ],
        );

        $reason = "Kategori dipilih manual dan pola '{$draft->description}' disimpan sebagai memory #{$memory->id}.";

        return $this->storeDraft($draft, $category, $telegramUser, $log, $reason, true);
    }

    private function storeDraft(
        TransactionDraft $draft,
        Category $category,
        TelegramUser $telegramUser,
        TransactionLog $log,
        string $reason,
        bool $learned,
    ): string
    {
        try {
            $transaction = $this->transactionCreator->createFromDraft($draft, $category, $telegramUser);

            $log->update([
                'transaction_id' => $transaction->id,
                'transaction_draft_id' => $draft->id,
                'status' => TransactionLogStatus::Processed,
                'processed_at' => now(),
            ]);

            return $this->messageFormatter->transactionSaved($transaction, $category, $reason, $learned);
        } catch (Throwable $exception) {
            $log->update([
                'status' => TransactionLogStatus::Failed,
                'error_code' => class_basename($exception),
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            report($exception);

            return 'Maaf, transaksi gagal disimpan. Coba lagi beberapa saat.';
        }
    }

    private function categoryQuestion(TelegramUser $telegramUser, string $reason = 'Kategori belum diketahui.', int $confidenceScore = 0): string
    {
        return $this->messageFormatter->categoryQuestion(
            $this->categoryResolver->choiceCategories($telegramUser->user),
            $reason,
            $confidenceScore,
        );
    }

    private function pendingDraft(TelegramUser $telegramUser): ?TransactionDraft
    {
        return TransactionDraft::query()
            ->where('telegram_user_id', $telegramUser->id)
            ->where('status', TransactionDraftStatus::Pending)
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    private function cancelPendingDraft(TelegramUser $telegramUser): string
    {
        $draft = $this->pendingDraft($telegramUser);

        if (! $draft) {
            return 'Tidak ada transaksi draft yang perlu dibatalkan.';
        }

        $draft->update(['status' => TransactionDraftStatus::Cancelled]);

        return 'Draft transaksi dibatalkan.';
    }
}
