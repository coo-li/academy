<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'personio_department',
    ];

    public function globalBudgets(): HasMany
    {
        return $this->hasMany(GlobalBudget::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getBudgetForYear(int $year, ?string $category = null, ?string $budgetType = null): float
    {
        $query = $this->globalBudgets()->where('year', $year);

        if ($category) {
            $query->where('category', $category);
        }

        if ($budgetType) {
            $query->where('budget_type', $budgetType);
        }

        return (float) $query->sum('amount_planned');
    }

    public function getPlannedHoursForYear(int $year): float
    {
        return (float) $this->users()->sum('target_hours_per_year');
    }

    public function getActualHoursForYear(int $year): float
    {
        $userIds = $this->users()->pluck('id');
        
        return (float) BudgetEntry::whereIn('user_id', $userIds)
            ->whereYear('date', $year)
            ->where('cost_type', BudgetEntry::COST_TYPE_TIME)
            ->sum('amount');
    }

    public function getUtilizationRate(int $year): float
    {
        $planned = $this->getPlannedHoursForYear($year);
        
        if ($planned <= 0) {
            return 0;
        }

        return ($this->getActualHoursForYear($year) / $planned) * 100;
    }
}
