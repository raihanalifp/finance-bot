<?php

use App\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Stores user-owned income and expense categories used by transactions, budgets, and Telegram parsing.
     * Design: Scoped by user_id for future multi-user support, type separates income/expense, slug keeps stable references,
     * and indexes optimize dashboard filters and category lookup.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->enum('type', [TransactionType::Income->value, TransactionType::Expense->value]);
            $table->string('icon', 64)->nullable();
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'type', 'slug']);
            $table->index(['user_id', 'type', 'is_active']);
            $table->index(['user_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
