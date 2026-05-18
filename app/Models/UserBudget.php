<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'total_allowance_monetary',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'total_allowance_monetary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getRemainingAllowanceAttribute(): float
    {
        $spent = $this->user->budgetEntries()
            ->whereYear('date', $this->year)
            ->where('is_deductible_from_allowance', true)
            ->where('cost_type', 'monetary')
            ->sum('amount');

        return max(0, (float) $this->total_allowance_monetary - (float) $spent);
    }
}
