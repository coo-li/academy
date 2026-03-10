<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Module extends Model
{
    protected $fillable = [
        'career_level_id',
        'title',
        'description',
        'skill_category_id',
        'accountable_type',
        'accountable_user_id',
        'method_id',
        'is_mandatory',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_mandatory' => 'boolean',
        ];
    }

    public function careerLevel(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class);
    }

    public function skillCategory(): BelongsTo
    {
        return $this->belongsTo(SkillCategory::class);
    }

    public function accountableUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountable_user_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(Method::class);
    }

    /**
     * Resolve the accountable person for a specific participant.
     * - type 'user': returns the fixed accountable teacher
     * - type 'head_of': returns the participant's own head-of
     */
    public function getAccountableFor(User $user): ?User
    {
        return match ($this->accountable_type) {
            'user' => $this->accountableUser,
            'head_of' => $user->headOf,
            default => null,
        };
    }

    public function accountableLabel(): string
    {
        return match ($this->accountable_type) {
            'user' => $this->accountableUser?->name ?? 'Nicht zugewiesen',
            'head_of' => 'Eigener Head of',
            default => 'Nicht zugewiesen',
        };
    }

    public function trainingSessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class);
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'module_assignments')
            ->withPivot('assigned_by', 'assigned_at')
            ->withTimestamps();
    }

    public function isGlobal(): bool
    {
        return $this->career_level_id === null;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function portfolioUploads(): HasMany
    {
        return $this->hasMany(PortfolioUpload::class);
    }

    public function trainingMaterials(): HasMany
    {
        return $this->hasMany(TrainingMaterial::class);
    }

    public function methodLabel(): string
    {
        return $this->method?->name ?? '–';
    }
}
