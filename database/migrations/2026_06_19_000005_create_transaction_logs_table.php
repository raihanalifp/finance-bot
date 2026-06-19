<?php

use App\Enums\TransactionLogStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Provides an auditable processing trail for Telegram updates and transaction-related system actions.
     * Design: Stores payload snapshots, status transitions, and error context separately from transactions so failed
     * messages remain observable even when no transaction is created.
     */
    public function up(): void
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_users')->nullOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_draft_id')->nullable()->constrained('transaction_drafts')->nullOnDelete();
            $table->string('update_id', 64)->nullable();
            $table->string('chat_id', 64)->nullable();
            $table->string('message_id', 64)->nullable();
            $table->text('message_text')->nullable();
            $table->json('payload')->nullable();
            $table->enum('status', array_column(TransactionLogStatus::cases(), 'value'))->default(TransactionLogStatus::Received->value);
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index('update_id');
            $table->index(['chat_id', 'created_at']);
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['telegram_user_id', 'created_at']);
            $table->index(['transaction_id']);
            $table->index(['transaction_draft_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_logs');
    }
};
