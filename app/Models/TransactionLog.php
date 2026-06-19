<?php

namespace App\Models;

use App\Enums\TransactionLogStatus;
use Database\Factories\TransactionLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLog extends Model
{
    /** @use HasFactory<TransactionLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telegram_user_id',
        'transaction_id',
        'transaction_draft_id',
        'update_id',
        'chat_id',
        'message_id',
        'message_text',
        'payload',
        'status',
        'error_code',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => TransactionLogStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function transactionDraft(): BelongsTo
    {
        return $this->belongsTo(TransactionDraft::class);
    }
}
