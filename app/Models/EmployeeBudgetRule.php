<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmployeeBudgetRule extends Model
{
    use HasFactory;

    public const TYPE_BASE = 'base';
    public const TYPE_OVERLAY = 'overlay';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'rule_type',
        'max_money_budget',
        'max_cash_budget',
        'time_money_ratio',
        'hourly_rate_override',
        'working_hours_min',
        'working_hours_max',
        'priority',
        'is_default',
        'applies_to_departments',
        'applies_to_positions',
        'applies_to_levels',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_money_budget' => 'decimal:2',
            'max_cash_budget' => 'decimal:2',
            'time_money_ratio' => 'decimal:2',
            'hourly_rate_override' => 'decimal:2',
            'working_hours_min' => 'decimal:2',
            'working_hours_max' => 'decimal:2',
            'priority' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'applies_to_departments' => 'array',
            'applies_to_positions' => 'array',
            'applies_to_levels' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EmployeeBudgetRule $rule) {
            if (empty($rule->slug)) {
                $rule->slug = Str::slug($rule->name);
            }
        });
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'employee_budget_rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeBase($query)
    {
        return $query->where('rule_type', self::TYPE_BASE);
    }

    public function scopeOverlay($query)
    {
        return $query->where('rule_type', self::TYPE_OVERLAY);
    }

    public function isBase(): bool
    {
        return $this->rule_type === self::TYPE_BASE;
    }

    public function isOverlay(): bool
    {
        return $this->rule_type === self::TYPE_OVERLAY;
    }

    /**
     * Check if this rule matches a given user based on all criteria.
     * For base rules: is_default=true always matches.
     * For overlay rules: is_default is ignored (overlays must have explicit criteria).
     */
    public function matchesUser(User $user): bool
    {
        if ($this->isBase() && $this->is_default) {
            return true;
        }

        if ($this->matchesDepartment($user) 
            || $this->matchesPosition($user) 
            || $this->matchesLevel($user)
            || $this->matchesWorkingHours($user)) {
            return true;
        }

        return false;
    }

    protected function matchesDepartment(User $user): bool
    {
        if (empty($this->applies_to_departments)) {
            return false;
        }

        $userDept = Str::lower(trim($user->personio_department ?? ''));
        
        foreach ($this->applies_to_departments as $dept) {
            if (Str::lower(trim($dept)) === $userDept) {
                return true;
            }
        }

        return false;
    }

    protected function matchesPosition(User $user): bool
    {
        if (empty($this->applies_to_positions)) {
            return false;
        }

        $userPosition = Str::lower(trim($user->personio_position ?? ''));
        
        foreach ($this->applies_to_positions as $position) {
            $pattern = Str::lower(trim($position));
            if (Str::contains($userPosition, $pattern)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesLevel(User $user): bool
    {
        if (empty($this->applies_to_levels)) {
            return false;
        }

        $userLevel = Str::lower(trim($user->personio_level_raw ?? ''));
        
        foreach ($this->applies_to_levels as $level) {
            if (Str::lower(trim($level)) === $userLevel) {
                return true;
            }
        }

        return false;
    }

    protected function matchesWorkingHours(User $user): bool
    {
        if ($this->working_hours_min === null && $this->working_hours_max === null) {
            return false;
        }

        $hours = $user->weekly_working_hours;
        
        if ($hours === null) {
            return false;
        }

        $minOk = $this->working_hours_min === null || $hours >= $this->working_hours_min;
        $maxOk = $this->working_hours_max === null || $hours <= $this->working_hours_max;

        return $minOk && $maxOk;
    }

    /**
     * Get the effective hourly rate for a user under this rule.
     */
    public function getEffectiveHourlyRate(User $user): float
    {
        if ($this->hourly_rate_override !== null) {
            return (float) $this->hourly_rate_override;
        }

        return $user->getHourlyRate();
    }

    /**
     * Get the default base rule.
     */
    public static function getDefault(): ?self
    {
        return static::active()->base()->default()->first();
    }

    /**
     * Count how many users would match this rule.
     */
    public function countMatchingUsers(): int
    {
        return User::active()
            ->whereNotNull('personio_id')
            ->get()
            ->filter(fn (User $user) => $this->matchesUser($user))
            ->count();
    }
}
