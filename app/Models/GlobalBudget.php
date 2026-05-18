<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlobalBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'team_id',
        'category',
        'budget_type',
        'amount_planned',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount_planned' => 'decimal:2',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByBudgetType($query, string $budgetType)
    {
        return $query->where('budget_type', $budgetType);
    }
}
