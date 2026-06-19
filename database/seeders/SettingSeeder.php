<?php

namespace Database\Seeders;

use App\Enums\SettingType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'admin@example.com')->first();

        if (! $user) {
            return;
        }

        $settings = [
            ['app.currency', 'IDR', SettingType::String, 'general', 'Default application currency.'],
            ['app.timezone', 'Asia/Jakarta', SettingType::String, 'general', 'Default timezone for reports and Telegram input.'],
            ['telegram.confirm_ambiguous_input', '1', SettingType::Boolean, 'telegram', 'Require confirmation for ambiguous Telegram transaction input.'],
            ['telegram.default_expense_category_slug', 'other-expense', SettingType::String, 'telegram', 'Fallback category for expense input.'],
            ['telegram.default_income_category_slug', 'other-income', SettingType::String, 'telegram', 'Fallback category for income input.'],
            ['reports.month_start_day', '1', SettingType::Integer, 'reports', 'First day of the monthly reporting cycle.'],
        ];

        foreach ($settings as [$key, $value, $type, $group, $description]) {
            Setting::query()->updateOrCreate(
                ['user_id' => $user->id, 'key' => $key],
                [
                    'value' => $value,
                    'type' => $type,
                    'group' => $group,
                    'is_encrypted' => false,
                    'description' => $description,
                ]
            );
        }
    }
}
