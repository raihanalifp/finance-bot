<?php

namespace App\Services\Telegram;

use App\DTOs\Telegram\IncomingTelegramMessageData;
use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionLogStatus;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\TransactionDraft;
use App\Models\TransactionLog;
use App\Models\TelegramUser;
use App\Services\Categories\CategoryMemoryService;
use App\Services\Transactions\TransactionCategoryResolver;
use App\Services\Transactions\TransactionCreator;
use App\Services\Transactions\TransactionTextParser;
use App\Services\Transactions\TransactionValidationService;
use Throwable;

class TelegramTransactionConversationService
{
    public function __construct(
        private readonly CommandRouter $commandRouter,
        private readonly TransactionTextParser $parser,
        private readonly TransactionValidationService $validationService,
        private readonly TransactionCategoryResolver $categoryResolver,
        private readonly TransactionCreator $transactionCreator,
        private readonly CategoryMemoryService $categoryMemoryService,
        private readonly TelegramResponseService $responseService,
    ) {}

    public function handle(IncomingTelegramMessageData $message, TelegramUser $telegramUser, TransactionLog $log): string
    {
        $text = trim($message->text);

        if ($this->commandRouter->isCommand($text)) {
            return $this->commandRouter->handle($text, $telegramUser, $log);
        }

        $pendingDraft = $this->pendingDraft($telegramUser);

        if ($pendingDraft) {
            return $this->handlePendingDraft($text, $pendingDraft, $telegramUser, $log);
        }

        return $this->createDraftFromText($text, $telegramUser, $log);
    }

    private function handlePendingDraft(string $text, TransactionDraft $draft, TelegramUser $telegramUser, TransactionLog $log): string
    {
        $state = $draft->parser_result['conversation_state'] ?? 'confirm_transaction';

        if ($state === 'choose_category') {
            if (preg_match('/^\d+$/', $text)) {
                return $this->confirmCategory((int) $text, $draft, $telegramUser, $log);
            }

            return $this->categoryQuestion($telegramUser, 'Balas dengan nomor kategori.');
        }

        return match ($text) {
            '1' => $this->confirmDraft($draft, $telegramUser, $log),
            '2' => $this->moveDraftToCategorySelection($draft, $telegramUser),
            '3' => $this->cancelDraft($draft),
            default => $this->confirmationQuestion($draft, $telegramUser),
        };
    }

    private function createDraftFromText(string $text, TelegramUser $telegramUser, TransactionLog $log): string
    {
        $parsed = $this->parser->parse($text);
        $validation = $this->validationService->validate($parsed);

        if (! $validation->valid) {
            $log->update([
                'status' => TransactionLogStatus::Failed,
                'error_code' => $validation->errorCode,
                'error_message' => $validation->message,
                'processed_at' => now(),
            ]);

            return $this->responseService->validationError((string) $validation->message);
        }

        $categoryResolution = $this->categoryResolver->resolve($telegramUser->user, $parsed);
        $category = $categoryResolution->category ?? ($parsed->type === TransactionType::Expense
            ? $this->categoryResolver->fallbackExpenseCategory($telegramUser->user)
            : null);

        $draft = TransactionDraft::query()->create([
            'user_id' => $telegramUser->user_id,
            'telegram_user_id' => $telegramUser->id,
            'category_id' => $category?->id,
            'type' => $parsed->type,
            'amount' => $parsed->amount,
            'currency' => 'IDR',
            'description' => $parsed->description,
            'transaction_date' => ($parsed->transactionDate ?? now())->toDateString(),
            'transaction_time' => now()->format('H:i:s'),
            'raw_text' => $parsed->rawText,
            'confidence_score' => $parsed->confidenceScore,
            'parser_result' => [
                'tokens' => $parsed->tokens,
                'category_slug' => $parsed->categorySlug,
                'category_resolved' => $categoryResolution->category !== null,
                'category_strategy' => $categoryResolution->strategy,
                'category_confidence_score' => $categoryResolution->confidenceScore,
                'category_reason' => $categoryResolution->reason,
                'category_memory_id' => $categoryResolution->memory?->id,
                'conversation_state' => 'confirm_transaction',
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

        if (! $category) {
            $this->moveDraftState($draft, 'choose_category');

            return $this->categoryQuestion($telegramUser, $categoryResolution->reason, $categoryResolution->confidenceScore);
        }

        return $this->responseService->confirmationQuestion($draft, $category);
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

    private function confirmDraft(TransactionDraft $draft, TelegramUser $telegramUser, TransactionLog $log): string
    {
        $category = $draft->category;

        if (! $category) {
            return $this->moveDraftToCategorySelection($draft, $telegramUser);
        }

        return $this->storeDraft($draft, $category, $telegramUser, $log, 'Transaksi dikonfirmasi manual.', false);
    }

    private function moveDraftToCategorySelection(TransactionDraft $draft, TelegramUser $telegramUser): string
    {
        $this->moveDraftState($draft, 'choose_category');

        return $this->categoryQuestion($telegramUser, 'Pilih kategori yang sesuai.');
    }

    private function cancelDraft(TransactionDraft $draft): string
    {
        $draft->update(['status' => TransactionDraftStatus::Cancelled]);

        return $this->responseService->cancelled();
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

            return $this->responseService->transactionSaved($transaction, $category, $reason, $learned);
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
        return $this->responseService->categoryQuestion(
            $this->categoryResolver->choiceCategories($telegramUser->user),
            $reason,
            $confidenceScore,
        );
    }

    private function confirmationQuestion(TransactionDraft $draft, TelegramUser $telegramUser): string
    {
        $category = $draft->category ?? $this->categoryResolver->fallbackExpenseCategory($telegramUser->user);

        if (! $category) {
            return $this->moveDraftToCategorySelection($draft, $telegramUser);
        }

        return $this->responseService->confirmationQuestion($draft, $category);
    }

    private function moveDraftState(TransactionDraft $draft, string $state): void
    {
        $parserResult = $draft->parser_result ?? [];
        $parserResult['conversation_state'] = $state;

        $draft->update(['parser_result' => $parserResult]);
        $draft->refresh();
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

}
