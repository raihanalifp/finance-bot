<?php

use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Stores finalized financial movements from Telegram, dashboard, import, or system events.
     * Design: amount is always positive and direction is represented by type; category is nullable for uncategorized input;
     * uuid is used as a safe public identifier; raw_text and metadata preserve parser context for future AI.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_users')->nullOnDelete();
            $table->enum('type', array_column(TransactionType::cases(), 'value'));
            $table->decimal('amount', 18, 2);
            $table->string('currency', 3)->default('IDR');
            $table->string('description', 500);
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->time('transaction_time')->nullable();
            $table->enum('source', array_column(TransactionSource::cases(), 'value'))->default(TransactionSource::Dashboard->value);
            $table->text('raw_text')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['user_id', 'transaction_date']);
            $table->index(['user_id', 'type', 'transaction_date']);
            $table->index(['user_id', 'category_id', 'transaction_date']);
            $table->index(['user_id', 'source', 'created_at']);
            $table->index(['telegram_user_id', 'created_at']);
            $table->index(['deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
