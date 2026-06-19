<?php

namespace Database\Seeders;

use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Database\Seeder;

class TelegramUserSeeder extends Seeder
{
    public function run(): void
    {
        $chatId = env('TELEGRAM_ALLOWED_CHAT_ID');
        $user = User::query()->where('email', 'admin@example.com')->first();

        if (! $user || blank($chatId)) {
            return;
        }

        TelegramUser::query()->updateOrCreate(
            ['telegram_chat_id' => (string) $chatId],
            [
                'user_id' => $user->id,
                'telegram_user_id' => env('TELEGRAM_ALLOWED_USER_ID'),
                'username' => env('TELEGRAM_ALLOWED_USERNAME'),
                'is_authorized' => true,
                'last_interaction_at' => null,
            ]
        );
    }
}
