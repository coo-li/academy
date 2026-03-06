<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
        return $this->hasRole(['admin', 'people_manager', 'head_of']);
    }

    public function isTeacher(): bool
    {
        return $this->hasRole(['admin', 'people_manager', 'head_of', 'trainer']);
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

    public function careerLevel(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class);
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

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function portfolioUploads(): HasMany
    {
        return $this->hasMany(PortfolioUpload::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
