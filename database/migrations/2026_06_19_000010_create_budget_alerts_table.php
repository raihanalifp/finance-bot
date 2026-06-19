<?php

use App\Enums\BudgetAlertThreshold;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Tracks monthly budget threshold notifications already sent to Telegram.
     * Design: A unique budget/month/threshold key prevents duplicate 80% and 100% alerts while keeping a history
     * of when a budget crossed critical levels.
     */
    public function up(): void
    {
        Schema::create('budget_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('threshold');
            $table->decimal('budget_amount', 18, 2);
            $table->decimal('spent_amount', 18, 2);
            $table->decimal('percentage', 8, 2);
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['monthly_budget_id', 'year', 'month', 'threshold'], 'budget_alert_unique_threshold');
            $table->index(['user_id', 'year', 'month']);
            $table->index(['category_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_alerts');
    }
};
