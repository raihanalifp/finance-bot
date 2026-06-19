<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\MonthlyBudget> */
class MonthlyBudgetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->state(['type' => TransactionType::Expense]),
            'year' => (int) now()->format('Y'),
            'month' => (int) now()->format('m'),
            'amount' => fake()->randomFloat(2, 500000, 5000000),
            'currency' => 'IDR',
            'is_active' => true,
            'notes' => null,
        ];
    }
}
