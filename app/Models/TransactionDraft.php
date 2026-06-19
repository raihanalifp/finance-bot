<?php

namespace App\Models;

use App\Enums\TransactionDraftStatus;
use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use Database\Factories\TransactionDraftFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionDraft extends Model
{
    /** @use HasFactory<TransactionDraftFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'uuid',
        'user_id',
        'telegram_user_id',
        'category_id',
        'type',
        'amount',
        'currency',
        'description',
        'transaction_date',
        'transaction_time',
        'source',
        'raw_text',
        'confidence_score',
        'parser_result',
        'status',
        'expires_at',
        'confirmed_at',
        'transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
            'transaction_time' => 'datetime:H:i:s',
            'source' => TransactionSource::class,
            'confidence_score' => 'integer',
            'parser_result' => 'array',
            'status' => TransactionDraftStatus::class,
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function telegramUser(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(TransactionLog::class);
    }
}
