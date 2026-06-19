<?php

use App\Enums\SettingType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Stores user-scoped and global application configuration such as currency, timezone, and Telegram behavior.
     * Design: Key/value shape keeps personal settings flexible without frequent migrations; type supports safe casting;
     * user_id nullable allows global defaults while user-scoped keys override them.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->enum('type', array_column(SettingType::cases(), 'value'))->default(SettingType::String->value);
            $table->string('group')->default('general');
            $table->boolean('is_encrypted')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key'], 'settings_user_key_unique');
            $table->index(['group', 'key']);
            $table->index(['user_id', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
