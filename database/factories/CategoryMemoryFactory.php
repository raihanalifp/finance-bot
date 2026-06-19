<?php

namespace Database\Factories;

use App\Enums\CategoryMemorySource;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<\App\Models\CategoryMemory> */
class CategoryMemoryFactory extends Factory
{
    public function definition(): array
    {
        $phrase = fake()->words(2, true);

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory()->state(['type' => TransactionType::Expense]),
            'phrase' => $phrase,
            'normalized_phrase' => Str::of($phrase)->lower()->squish()->toString(),
            'keywords' => explode(' ', $phrase),
            'confidence_score' => fake()->numberBetween(70, 95),
            'match_count' => fake()->numberBetween(1, 10),
            'source' => CategoryMemorySource::UserConfirmed,
            'last_matched_at' => now(),
            'is_active' => true,
            'metadata' => null,
        ];
    }
}
