<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramApiClient
{
    public function sendMessage(string $chatId, string $text): void
    {
        $token = config('services.telegram.bot_token');

        if (blank($token)) {
            Log::warning('Telegram bot token is not configured.', ['chat_id' => $chatId, 'message' => $text]);

            return;
        }

        $response = Http::asJson()
            ->timeout(10)
            ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $text,
            ]);

        if ($response->failed()) {
            Log::error('Failed to send Telegram message.', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
        }
    }
}
