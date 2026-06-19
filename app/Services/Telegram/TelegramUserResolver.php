<?php

namespace App\Services\Telegram;

use App\DTOs\Telegram\IncomingTelegramMessageData;
use App\Enums\AuditAction;
use App\Exceptions\Telegram\UnauthorizedTelegramUserException;
use App\Models\TelegramUser;
use App\Services\Security\AuditLogService;

class TelegramUserResolver
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function resolve(IncomingTelegramMessageData $message): TelegramUser
    {
        $this->ensureChatIdWhitelisted($message);

        $telegramUser = TelegramUser::query()->where('telegram_chat_id', $message->chatId)->first();

        if (! $telegramUser || ! $telegramUser->is_authorized) {
            $this->auditLogService->record(AuditAction::SecurityBlocked, context: [
                'reason' => 'telegram_chat_not_authorized',
                'chat_id' => $message->chatId,
                'telegram_user_id' => $message->telegramUserId,
                'username' => $message->username,
            ]);

            throw new UnauthorizedTelegramUserException('Telegram chat is not authorized.');
        }

        $telegramUser->update([
            'telegram_user_id' => $telegramUser->telegram_user_id ?: $message->telegramUserId,
            'username' => $message->username ?? $telegramUser->username,
            'first_name' => $message->firstName ?? $telegramUser->first_name,
            'last_name' => $message->lastName ?? $telegramUser->last_name,
            'last_interaction_at' => now(),
        ]);

        return $telegramUser;
    }

    private function ensureChatIdWhitelisted(IncomingTelegramMessageData $message): void
    {
        $allowedChatIds = config('services.telegram.allowed_chat_ids', []);

        if ($allowedChatIds !== [] && ! in_array($message->chatId, $allowedChatIds, true)) {
            $this->auditLogService->record(AuditAction::SecurityBlocked, context: [
                'reason' => 'telegram_chat_not_whitelisted',
                'chat_id' => $message->chatId,
                'telegram_user_id' => $message->telegramUserId,
                'username' => $message->username,
            ]);

            throw new UnauthorizedTelegramUserException('Telegram chat is not whitelisted.');
        }
    }
}
