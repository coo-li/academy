<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamBudgetAllocation extends Model
{
    protected $fillable = [
        'year',
        'team_id',
        'allocation_percentage',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'allocation_percentage' => 'decimal:2',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getAllocatedBudgetAttribute(): float
    {
        $companySetting = CompanyBudgetSetting::forYear($this->year);
        if (!$companySetting) {
            return 0;
        }

        return $companySetting->service_dev_budget * ($this->allocation_percentage / 100);
    }
}
