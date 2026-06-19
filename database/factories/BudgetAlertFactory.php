<?php

namespace Database\Factories;

use App\Models\MonthlyBudget;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\BudgetAlert> */
class BudgetAlertFactory extends Factory
{
    public function definition(): array
    {
        $budget = MonthlyBudget::factory()->create();

        return [
            'monthly_budget_id' => $budget->id,
            'user_id' => $budget->user_id,
            'category_id' => $budget->category_id,
            'year' => $budget->year,
            'month' => $budget->month,
            'threshold' => fake()->randomElement([80, 100]),
            'budget_amount' => $budget->amount,
            'spent_amount' => fake()->randomFloat(2, (float) $budget->amount * 0.8, (float) $budget->amount * 1.2),
            'percentage' => fake()->randomFloat(2, 80, 120),
            'sent_at' => now(),
            'metadata' => null,
        ];
    }
}
