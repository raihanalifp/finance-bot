<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Stores monthly spending plans globally or per expense category.
     * Design: One row per user/category/month avoids overlapping active budgets and allows fast budget-vs-actual queries;
     * category_id nullable represents the user's total monthly budget. The application layer uses updateOrCreate to
     * keep total-budget rows unique because MySQL nullable unique keys intentionally allow multiple NULL values.
     */
    public function up(): void
    {
        Schema::create('monthly_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('IDR');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'category_id', 'year', 'month'], 'monthly_budgets_user_category_month_unique');
            $table->index(['user_id', 'year', 'month', 'is_active']);
            $table->index(['category_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_budgets');
    }
};
