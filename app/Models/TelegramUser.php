<?php

namespace App\Models;

use Database\Factories\TelegramUserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramUser extends Model
{
    /** @use HasFactory<TelegramUserFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'telegram_chat_id',
        'telegram_user_id',
        'username',
        'first_name',
        'last_name',
        'is_authorized',
        'last_interaction_at',
    ];

    protected function casts(): array
    {
        return [
            'is_authorized' => 'boolean',
            'last_interaction_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function transactionDrafts(): HasMany
    {
        return $this->hasMany(TransactionDraft::class);
    }

    public function transactionLogs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }
}
