<?php

namespace App\Services\Telegram;

use App\DTOs\Telegram\IncomingTelegramMessageData;
use App\Enums\AuditAction;
use App\Enums\TransactionLogStatus;
use App\Exceptions\Telegram\InvalidTelegramPayloadException;
use App\Exceptions\Telegram\UnauthorizedTelegramUserException;
use App\Exceptions\Telegram\UnauthorizedTelegramWebhookException;
use App\Models\TransactionLog;
use App\Services\Security\AuditLogService;
use Throwable;

class TelegramWebhookService
{
    public function __construct(
        private readonly TelegramUserResolver $userResolver,
        private readonly TelegramTransactionConversationService $conversationService,
        private readonly TelegramApiClient $telegramApiClient,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function handle(string $secret, array $payload): void
    {
        $this->validateSecret($secret);

        $message = IncomingTelegramMessageData::fromPayload($payload);

        if (! $message->isValid()) {
            throw new InvalidTelegramPayloadException('Telegram payload does not contain a valid text message.');
        }

        $log = TransactionLog::query()->create([
            'update_id' => $message->updateId,
            'chat_id' => $message->chatId,
            'message_id' => $message->messageId,
            'message_text' => $message->text,
            'payload' => $payload,
            'status' => TransactionLogStatus::Received,
        ]);

        try {
            $telegramUser = $this->userResolver->resolve($message);

            $log->update([
                'user_id' => $telegramUser->user_id,
                'telegram_user_id' => $telegramUser->id,
            ]);

            $responseText = $this->conversationService->handle($message, $telegramUser, $log);
            $this->telegramApiClient->sendMessage($message->chatId, $responseText);
            $this->auditLogService->record(AuditAction::TelegramProcessed, entity: $log, context: [
                'chat_id' => $message->chatId,
                'message_id' => $message->messageId,
            ], userId: $telegramUser->user_id);
        } catch (UnauthorizedTelegramUserException $exception) {
            $log->update([
                'status' => TransactionLogStatus::Ignored,
                'error_code' => 'unauthorized_telegram_user',
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $log->update([
                'status' => TransactionLogStatus::Failed,
                'error_code' => class_basename($exception),
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);

            report($exception);
            $this->auditLogService->record(AuditAction::SecurityBlocked, entity: $log, context: [
                'reason' => 'telegram_processing_failed',
                'exception' => class_basename($exception),
                'message' => $exception->getMessage(),
            ]);
            $this->telegramApiClient->sendMessage($message->chatId, 'Maaf, terjadi error saat memproses pesan.');
        }
    }

    private function validateSecret(string $secret): void
    {
        $configuredSecret = config('services.telegram.webhook_secret');

        if (blank($configuredSecret) || ! hash_equals((string) $configuredSecret, $secret)) {
            $this->auditLogService->record(AuditAction::SecurityBlocked, context: [
                'reason' => 'invalid_telegram_webhook_secret',
            ]);

            throw new UnauthorizedTelegramWebhookException('Invalid Telegram webhook secret.');
        }
    }
}
