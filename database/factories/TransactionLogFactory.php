<?php

namespace Database\Factories;

use App\Enums\TransactionLogStatus;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\TransactionLog> */
class TransactionLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'telegram_user_id' => TelegramUser::factory(),
            'transaction_id' => null,
            'transaction_draft_id' => null,
            'update_id' => (string) fake()->unique()->numberBetween(1, 999999),
            'chat_id' => (string) fake()->numberBetween(100000000, 999999999),
            'message_id' => (string) fake()->numberBetween(1, 999999),
            'message_text' => 'kopi 18000',
            'payload' => ['update_id' => fake()->numberBetween(1, 999999)],
            'status' => TransactionLogStatus::Received,
            'error_code' => null,
            'error_message' => null,
            'processed_at' => null,
        ];
    }
}
