<?php

namespace App\Services\Telegram;

use App\Models\TelegramUser;
use App\Models\TransactionLog;

class CommandRouter
{
    public function __construct(private readonly TelegramCommandService $commandService) {}

    public function isCommand(string $text): bool
    {
        return str_starts_with(trim($text), '/');
    }

    public function handle(string $text, TelegramUser $telegramUser, TransactionLog $log): string
    {
        $parts = preg_split('/\s+/', trim($text), 2);
        $command = strtolower($parts[0] ?? '');
        $arguments = trim($parts[1] ?? '');

        return $this->commandService->handle($command, $arguments, $telegramUser, $log);
    }
}
