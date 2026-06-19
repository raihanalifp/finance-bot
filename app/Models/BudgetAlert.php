<?php

namespace App\Models;

use Database\Factories\BudgetAlertFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAlert extends Model
{
    /** @use HasFactory<BudgetAlertFactory> */
    use HasFactory;

    protected $fillable = [
        'monthly_budget_id',
        'user_id',
        'category_id',
        'year',
        'month',
        'threshold',
        'budget_amount',
        'spent_amount',
        'percentage',
        'sent_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'threshold' => 'integer',
            'budget_amount' => 'decimal:2',
            'spent_amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'sent_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function monthlyBudget(): BelongsTo
    {
        return $this->belongsTo(MonthlyBudget::class);
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
