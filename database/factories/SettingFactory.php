<?php

namespace Database\Factories;

use App\Enums\SettingType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<\App\Models\Setting> */
class SettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'key' => fake()->unique()->slug(2),
            'value' => fake()->word(),
            'type' => SettingType::String,
            'group' => 'general',
            'is_encrypted' => false,
            'description' => fake()->sentence(),
        ];
    }
}
