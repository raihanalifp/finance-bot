<?php

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->first();

        if (! $user) {
            return;
        }

        $categories = [
            TransactionType::Expense->value => [
                ['Food & Drink', 'utensils', '#f97316'],
                ['Transport', 'car', '#3b82f6'],
                ['Groceries', 'shopping-basket', '#22c55e'],
                ['Bills', 'receipt', '#ef4444'],
                ['Shopping', 'shopping-bag', '#a855f7'],
                ['Entertainment', 'film', '#ec4899'],
                ['Health', 'heart-pulse', '#14b8a6'],
                ['Education', 'book-open', '#6366f1'],
                ['Family', 'users', '#84cc16'],
                ['Other Expense', 'circle-help', '#64748b'],
            ],
            TransactionType::Income->value => [
                ['Salary', 'briefcase', '#16a34a'],
                ['Bonus', 'gift', '#65a30d'],
                ['Freelance', 'laptop', '#0891b2'],
                ['Gift', 'present', '#db2777'],
                ['Investment', 'trending-up', '#7c3aed'],
                ['Other Income', 'circle-plus', '#475569'],
            ],
        ];

        foreach ($categories as $type => $items) {
            foreach ($items as $index => [$name, $icon, $color]) {
                Category::query()->updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'type' => $type,
                        'slug' => Str::slug($name),
                    ],
                    [
                        'name' => $name,
                        'icon' => $icon,
                        'color' => $color,
                        'sort_order' => $index + 1,
                        'is_system' => true,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
