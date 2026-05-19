<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $with = ['roles'];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'personio_id',
        'personio_position',
        'personio_department',
        'personio_level_raw',
        'personio_path_raw',
        'personio_synced_at',
        'career_level_id',
        'head_of_user_id',
        'team_id',
        'archived_at',
        'invited_at',
        'target_hours_per_year',
        'budget_tracker_id',
        'employee_category',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'personio_synced_at' => 'datetime',
            'archived_at' => 'datetime',
            'invited_at' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(string|array $slugs): bool
    {
        $slugs = (array) $slugs;

        return $this->roles->contains(fn (Role $role) => in_array($role->slug, $slugs));
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isManager(): bool
    {
        return $this->hasRole(['admin', 'c_level', 'people_manager', 'head_of']);
    }

    public function isSchulungsmanager(): bool
    {
        return $this->hasRole(['admin', 'schulungsmanager']);
    }

    public function isTrainer(): bool
    {
        return $this->hasRole(['admin', 'trainer']);
    }

    /** @deprecated Use isTrainer() or isSchulungsmanager() instead */
    public function isTeacher(): bool
    {
        return $this->hasRole(['admin', 'people_manager', 'head_of', 'trainer', 'schulungsmanager']);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function positionMapping(): ?PersonioPositionMapping
    {
        if (! $this->personio_position) {
            return null;
        }

        return PersonioPositionMapping::where('personio_position', $this->personio_position)->first();
    }

    public function hasCareerPath(): bool
    {
        return $this->career_level_id !== null;
    }

    /**
     * Primary career level (auto-synced from pivot, kept for backward compat).
     */
    public function careerLevel(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class);
    }

    /**
     * All career levels (source of truth). A user can be on multiple paths.
     */
    public function careerLevels(): BelongsToMany
    {
        return $this->belongsToMany(CareerLevel::class)->withTimestamps();
    }

    /**
     * Modules from all assigned career levels, deduplicated.
     */
    public function allCareerModules(): Collection
    {
        return $this->careerLevels->load('modules')
            ->flatMap(fn (CareerLevel $level) => $level->modules)
            ->unique('id')
            ->values();
    }

    /**
     * Add a career level to the pivot and sync the primary FK.
     */
    public function addCareerLevel(CareerLevel $level): void
    {
        // #region agent log
        @file_put_contents('/root/.cursor/debug-562df2.log', json_encode(['sessionId' => '562df2', 'hypothesisId' => 'D', 'location' => 'User:addCareerLevel', 'message' => 'career_level_being_added', 'data' => ['user' => $this->name, 'user_id' => $this->id, 'level_id' => $level->id, 'level_title' => $level->title, 'path_name' => $level->careerPath?->name, 'personio_level_raw' => $this->personio_level_raw, 'trace' => collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5))->map(fn($f) => ($f['class'] ?? '') . '::' . ($f['function'] ?? '') . ':' . ($f['line'] ?? ''))->toArray()], 'timestamp' => round(microtime(true) * 1000)]) . "\n", FILE_APPEND);
        // #endregion

        $this->careerLevels()->syncWithoutDetaching([$level->id]);
        $this->load('careerLevels');
        $this->syncPrimaryCareerLevel();
    }

    /**
     * Remove a career level from the pivot and sync the primary FK.
     */
    public function removeCareerLevel(CareerLevel $level): void
    {
        $this->careerLevels()->detach($level->id);
        $this->load('careerLevels');
        $this->syncPrimaryCareerLevel();
    }

    /**
     * Replace one career level with another in the pivot (e.g. level advancement).
     */
    public function replaceCareerLevel(CareerLevel $old, CareerLevel $new): void
    {
        $this->careerLevels()->detach($old->id);
        $this->careerLevels()->syncWithoutDetaching([$new->id]);
        $this->load('careerLevels');
        $this->syncPrimaryCareerLevel();
    }

    /**
     * Keep users.career_level_id in sync with the oldest pivot entry.
     */
    public function syncPrimaryCareerLevel(): void
    {
        $firstLevel = $this->careerLevels()
            ->orderBy('career_level_user.created_at')
            ->first();

        $this->updateQuietly(['career_level_id' => $firstLevel?->id]);
    }

    public function headOf(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_of_user_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(User::class, 'head_of_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function managedTeams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'manager_team')->withTimestamps();
    }

    /**
     * Resolve the People Manager responsible for this user via team assignment.
     * Only considers users with the 'people_manager' role (not head_of).
     * Includes the user themselves if they are their own People Manager.
     */
    public function getPeopleManager(): ?User
    {
        if (! $this->team) {
            return null;
        }

        return $this->team->managers()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'people_manager'))
            ->first();
    }

    /**
     * All active employees in teams managed by this user.
     * Admins see everyone.
     */
    public function managedEmployees(): Builder
    {
        if ($this->isAdmin()) {
            return User::active()->whereKeyNot($this->id);
        }

        $teamIds = $this->managedTeams()->pluck('teams.id');

        return User::active()
            ->whereIn('team_id', $teamIds)
            ->whereKeyNot($this->id);
    }

    /**
     * Only employees from explicitly managed teams (ignores admin override).
     * Head-Ofs are filtered out for non-C-Level managers (they appear on C-Level dashboard instead).
     */
    public function teamEmployees(): Builder
    {
        $teamIds = $this->managedTeams()->pluck('teams.id');

        $query = User::active()
            ->whereIn('team_id', $teamIds)
            ->whereKeyNot($this->id);

        if (! $this->isCLevel()) {
            $query->whereDoesntHave('roles', fn ($q) => $q->where('slug', 'head_of'));
        }

        return $query;
    }

    public function isPeopleManagerOrHeadOf(): bool
    {
        return $this->hasRole(['people_manager', 'head_of']);
    }

    public function isCLevel(): bool
    {
        return $this->hasRole('c_level');
    }

    public function hasHeadOfRole(): bool
    {
        return $this->hasRole('head_of');
    }

    /**
     * All active users with head_of role (for C-Level dashboard).
     * Returns users regardless of their team assignment.
     */
    public function headOfReports(): Builder
    {
        return User::active()
            ->whereHas('roles', fn ($q) => $q->where('slug', 'head_of'))
            ->whereKeyNot($this->id);
    }

    public function trainableModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_trainer')
            ->withTimestamps();
    }

    public function assignedModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'module_assignments')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function disabledCareerModules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'disabled_career_modules')
            ->withPivot('disabled_by', 'disabled_at')
            ->withTimestamps();
    }

    public function isCareerModuleDisabled(int $moduleId): bool
    {
        return $this->disabledCareerModules->contains('id', $moduleId);
    }

    /**
     * Active career modules (minus disabled) + individually assigned modules, deduplicated.
     */
    public function effectiveModules(): Collection
    {
        $this->loadMissing(['careerLevels.modules', 'disabledCareerModules', 'assignedModules']);

        $disabledIds = $this->disabledCareerModules->pluck('id')->toArray();

        $careerModules = $this->careerLevels
            ->flatMap(fn (CareerLevel $level) => $level->modules)
            ->unique('id')
            ->reject(fn (Module $m) => in_array($m->id, $disabledIds));

        return $careerModules
            ->merge($this->assignedModules)
            ->unique('id')
            ->values();
    }

    public function disabledMilestones(): BelongsToMany
    {
        return $this->belongsToMany(Milestone::class, 'disabled_milestones')
            ->withPivot('disabled_by', 'disabled_at')
            ->withTimestamps();
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function moduleInterests(): HasMany
    {
        return $this->hasMany(ModuleInterest::class);
    }

    public function portfolioUploads(): HasMany
    {
        return $this->hasMany(PortfolioUpload::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function getInitialsAttribute(): string
    {
        $name = trim($this->name ?? '');

        if ($name === '') {
            return '??';
        }

        return mb_strtoupper(mb_substr($name, 0, 2, 'UTF-8'), 'UTF-8');
    }

    // ========== Budget Dashboard Erweiterungen ==========

    public function budgetEntries(): HasMany
    {
        return $this->hasMany(BudgetEntry::class);
    }

    public function userBudgets(): HasMany
    {
        return $this->hasMany(UserBudget::class);
    }

    public function getBudgetForYear(int $year): ?UserBudget
    {
        return $this->userBudgets()->where('year', $year)->first();
    }

    public function getSpentBudgetForYear(int $year, bool $deductibleOnly = false): float
    {
        $query = $this->budgetEntries()
            ->whereYear('date', $year)
            ->where('cost_type', 'monetary');

        if ($deductibleOnly) {
            $query->where('is_deductible_from_allowance', true);
        }

        return (float) $query->sum('amount');
    }

    public function getRemainingAllowanceForYear(int $year): float
    {
        $budget = $this->getBudgetForYear($year);

        if (!$budget) {
            return 0;
        }

        return $budget->remaining_allowance;
    }

    public function getActualHoursForYear(int $year): float
    {
        return (float) $this->budgetEntries()
            ->whereYear('date', $year)
            ->where('cost_type', BudgetEntry::COST_TYPE_TIME)
            ->sum('amount');
    }

    public function getHourlyRate(): float
    {
        return $this->careerLevel?->hourly_rate ?? 100.00;
    }

    public function hasAdminAccess(): bool
    {
        return $this->hasRole(['admin', 'c_level']);
    }

    public function hasPeopleManagerAccess(): bool
    {
        return $this->hasRole(['admin', 'c_level', 'people_manager', 'head_of']);
    }
}
