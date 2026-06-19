<?php

namespace App\Models;

use App\Enums\SettingType;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'type',
        'group',
        'is_encrypted',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
            'is_encrypted' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typedValue(): mixed
    {
        $value = $this->is_encrypted && $this->value !== null ? Crypt::decryptString($this->value) : $this->value;

        return match ($this->type) {
            SettingType::Integer => $value === null ? null : (int) $value,
            SettingType::Decimal => $value === null ? null : (float) $value,
            SettingType::Boolean => filter_var($value, FILTER_VALIDATE_BOOL),
            SettingType::Json => $value === null ? null : json_decode($value, true, flags: JSON_THROW_ON_ERROR),
            default => $value,
        };
    }
}
