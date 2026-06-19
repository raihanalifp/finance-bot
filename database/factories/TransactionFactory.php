<?php

namespace Database\Factories;

use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Transaction> */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement([TransactionType::Income, TransactionType::Expense]);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->state(['type' => $type]),
            'telegram_user_id' => null,
            'type' => $type,
            'amount' => fake()->randomFloat(2, 10000, 1000000),
            'currency' => 'IDR',
            'description' => fake()->sentence(3),
            'notes' => null,
            'transaction_date' => fake()->dateTimeBetween('-2 months', 'now')->format('Y-m-d'),
            'transaction_time' => fake()->time(),
            'source' => TransactionSource::Dashboard,
            'raw_text' => null,
            'metadata' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function telegram(): static
    {
        return $this->state(fn () => [
            'telegram_user_id' => TelegramUser::factory(),
            'source' => TransactionSource::Telegram,
            'raw_text' => 'kopi 18000',
            'metadata' => ['message_id' => fake()->numberBetween(1, 9999)],
        ]);
    }
}
