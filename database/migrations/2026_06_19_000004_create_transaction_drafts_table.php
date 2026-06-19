<?php

use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Purpose: Stores parsed-but-not-finalized Telegram transaction candidates that require confirmation or correction.
     * Design: Drafts keep parser confidence, raw input, and expiry so ambiguous conversations can be resumed safely
     * without polluting the finalized transactions table.
     */
    public function up(): void
    {
        Schema::create('transaction_drafts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('telegram_user_id')->nullable()->constrained('telegram_users')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', array_column(TransactionType::cases(), 'value'))->nullable();
            $table->decimal('amount', 18, 2)->nullable();
            $table->string('currency', 3)->default('IDR');
            $table->string('description', 500)->nullable();
            $table->date('transaction_date')->nullable();
            $table->time('transaction_time')->nullable();
            $table->enum('source', array_column(TransactionSource::cases(), 'value'))->default(TransactionSource::Telegram->value);
            $table->text('raw_text');
            $table->unsignedTinyInteger('confidence_score')->nullable();
            $table->json('parser_result')->nullable();
            $table->enum('status', array_column(TransactionDraftStatus::cases(), 'value'))->default(TransactionDraftStatus::Pending->value);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['telegram_user_id', 'status', 'created_at']);
            $table->index(['expires_at']);
            $table->index(['transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_drafts');
    }
};
