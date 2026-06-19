<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\TelegramUser> */
class TelegramUserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'telegram_chat_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'telegram_user_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'username' => fake()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'is_authorized' => true,
            'last_interaction_at' => now(),
        ];
    }
}
