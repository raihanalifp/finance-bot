<?php

namespace Database\Factories;

use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\TransactionDraft> */
class TransactionDraftFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement([TransactionType::Income, TransactionType::Expense]);

        return [
            'user_id' => User::factory(),
            'telegram_user_id' => TelegramUser::factory(),
            'category_id' => Category::factory()->state(['type' => $type]),
            'type' => $type,
            'amount' => fake()->randomFloat(2, 10000, 500000),
            'currency' => 'IDR',
            'description' => fake()->sentence(3),
            'transaction_date' => now()->toDateString(),
            'transaction_time' => now()->format('H:i:s'),
            'source' => TransactionSource::Telegram,
            'raw_text' => 'makan 35000',
            'confidence_score' => fake()->numberBetween(60, 100),
            'parser_result' => ['parser' => 'rule_based'],
            'status' => TransactionDraftStatus::Pending,
            'expires_at' => now()->addMinutes(15),
            'confirmed_at' => null,
            'transaction_id' => null,
        ];
    }
}
