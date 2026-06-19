<?php

namespace App\DTOs\Telegram;

final readonly class IncomingTelegramMessageData
{
    public function __construct(
        public ?string $updateId,
        public string $chatId,
        public ?string $telegramUserId,
        public ?string $username,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $messageId,
        public string $text,
        public array $payload,
    ) {}

    public static function fromPayload(array $payload): self
    {
        $message = $payload['message'] ?? $payload['edited_message'] ?? [];
        $chat = $message['chat'] ?? [];
        $from = $message['from'] ?? [];

        return new self(
            updateId: isset($payload['update_id']) ? (string) $payload['update_id'] : null,
            chatId: (string) ($chat['id'] ?? ''),
            telegramUserId: isset($from['id']) ? (string) $from['id'] : null,
            username: $from['username'] ?? null,
            firstName: $from['first_name'] ?? null,
            lastName: $from['last_name'] ?? null,
            messageId: isset($message['message_id']) ? (string) $message['message_id'] : null,
            text: trim((string) ($message['text'] ?? '')),
            payload: $payload,
        );
    }

    public function isValid(): bool
    {
        return $this->chatId !== '' && $this->text !== '';
    }
}
