<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetEntry extends Model
{
    use HasFactory;

    public const TYPE_PERSONAL_GOAL = 'personal_goal';
    public const TYPE_INTERNAL_TRAINING = 'internal_training';
    public const TYPE_TEAM_GOAL = 'team_goal';
    public const TYPE_OTHER_INTERNAL = 'other_internal';

    public const COST_TYPE_MONETARY = 'monetary';
    public const COST_TYPE_TIME = 'time';

    public const BUDGET_TYPE_USED = 'used';
    public const BUDGET_TYPE_AVAILABLE = 'available';

    public const GOAL_CATEGORY_A = 'A';
    public const GOAL_CATEGORY_B = 'B';
    public const GOAL_CATEGORY_C = 'C';

    public const GOAL_CATEGORY_NONE = 'none';

    public const GOAL_CATEGORIES = [
        self::GOAL_CATEGORY_A => [
            'label' => 'A-Ziel',
            'description' => 'Hoher Impact + Hohe Dringlichkeit',
            'color' => 'red',
        ],
        self::GOAL_CATEGORY_B => [
            'label' => 'B-Ziel',
            'description' => 'Hoher Impact (nicht dringend) oder Niedriger Impact (dringend)',
            'color' => 'yellow',
        ],
        self::GOAL_CATEGORY_C => [
            'label' => 'C-Ziel',
            'description' => 'Niedriger Impact + Nicht dringend',
            'color' => 'gray',
        ],
        self::GOAL_CATEGORY_NONE => [
            'label' => 'Keine Kategorie',
            'description' => 'Bewusst ohne Priorisierung',
            'color' => 'slate',
        ],
    ];

    protected $fillable = [
        'user_id',
        'project_id',
        'label',
        'category',
        'budget_name',
        'type',
        'budget_type',
        'cost_type',
        'amount',
        'date',
        'month',
        'year',
        'is_deductible_from_allowance',
        'goal_category',
        'archived_at',
        'archived_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
            'is_deductible_from_allowance' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function archive(?int $userId = null): void
    {
        $this->update([
            'archived_at' => now(),
            'archived_by' => $userId,
        ]);
    }

    public function unarchive(): void
    {
        $this->update([
            'archived_at' => null,
            'archived_by' => null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePersonalGoals($query)
    {
        return $query->where('type', self::TYPE_PERSONAL_GOAL);
    }

    public function scopeTeamGoals($query)
    {
        return $query->where('type', self::TYPE_TEAM_GOAL);
    }

    public function scopeInternalTrainings($query)
    {
        return $query->where('type', self::TYPE_INTERNAL_TRAINING);
    }

    public function scopeDeductible($query)
    {
        return $query->where('is_deductible_from_allowance', true);
    }

    public function scopeForYear($query, int $year)
    {
        return $query->whereYear('date', $year);
    }

    public function scopeMonetary($query)
    {
        return $query->where('cost_type', self::COST_TYPE_MONETARY);
    }

    public function scopeTime($query)
    {
        return $query->where('cost_type', self::COST_TYPE_TIME);
    }

    public function scopeUsed($query)
    {
        return $query->where('budget_type', self::BUDGET_TYPE_USED);
    }

    public function scopeAvailable($query)
    {
        return $query->where('budget_type', self::BUDGET_TYPE_AVAILABLE);
    }

    public function scopeForMonth($query, int $month)
    {
        return $query->where('month', $month);
    }

    public function scopeForProject($query, string $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived($query)
    {
        return $query->whereNotNull('archived_at');
    }

    public function scopeOtherInternal($query)
    {
        return $query->where('type', self::TYPE_OTHER_INTERNAL);
    }

    public function scopeWithCategory($query, string $category)
    {
        return $query->where('goal_category', $category);
    }

    public function scopeUncategorized($query)
    {
        return $query->whereNull('goal_category');
    }

    public function scopeCategorized($query)
    {
        return $query->whereNotNull('goal_category');
    }

    public function getGoalCategoryLabelAttribute(): ?string
    {
        if (!$this->goal_category) {
            return null;
        }

        return self::GOAL_CATEGORIES[$this->goal_category]['label'] ?? null;
    }

    public function getGoalCategoryColorAttribute(): ?string
    {
        if (!$this->goal_category) {
            return null;
        }

        return self::GOAL_CATEGORIES[$this->goal_category]['color'] ?? null;
    }

    public static function getGoalCategoryOptions(): array
    {
        return collect(self::GOAL_CATEGORIES)->map(fn($config, $key) => [
            'value' => $key,
            'label' => $config['label'],
            'description' => $config['description'],
        ])->values()->toArray();
    }
}
