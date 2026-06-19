<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Maps an application user to authorized Telegram identities.
     * Design: telegram_chat_id is unique because Telegram input is trusted only after chat authorization;
     * authorization and last interaction fields support security checks and bot diagnostics.
     */
    public function up(): void
    {
        Schema::create('telegram_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('telegram_chat_id', 64)->unique();
            $table->string('telegram_user_id', 64)->nullable()->index();
            $table->string('username')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->boolean('is_authorized')->default(false);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_authorized']);
            $table->index('last_interaction_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_users');
    }
};
