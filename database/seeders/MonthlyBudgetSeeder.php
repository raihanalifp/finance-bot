<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\MonthlyBudget;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonthlyBudgetSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->first();

        if (! $user) {
            return;
        }

        $foodCategory = Category::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::Expense)
            ->where('slug', 'food-drink')
            ->first();

        MonthlyBudget::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'category_id' => $foodCategory?->id,
                'year' => (int) now()->format('Y'),
                'month' => (int) now()->format('m'),
            ],
            [
                'amount' => 1500000,
                'currency' => 'IDR',
                'is_active' => true,
                'notes' => 'Initial monthly food budget.',
            ]
        );
    }
}
