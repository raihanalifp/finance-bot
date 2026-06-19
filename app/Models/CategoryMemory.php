<?php

namespace App\Models;

use App\Enums\CategoryMemorySource;
use Database\Factories\CategoryMemoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryMemory extends Model
{
    /** @use HasFactory<CategoryMemoryFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'phrase',
        'normalized_phrase',
        'keywords',
        'confidence_score',
        'match_count',
        'source',
        'last_matched_at',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'confidence_score' => 'integer',
            'match_count' => 'integer',
            'source' => CategoryMemorySource::class,
            'last_matched_at' => 'datetime',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
