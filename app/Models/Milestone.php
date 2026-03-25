<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Milestone extends Model
{
    protected $fillable = [
        'career_level_id',
        'team_id',
        'category',
        'title',
        'description',
        'type',
        'sort_order',
    ];

    public const CATEGORIES = [
        'quantitative'  => 'Quantitative Faktoren',
        'qualitative'   => 'Qualitative Faktoren',
        'softskills'    => 'Softskills',
        'spezifisch'    => 'Spezifische Anforderungen',
        'weiterbildung' => 'Weiterbildung',
    ];

    public const TYPES = [
        'passiv' => 'passiv / on-the-job',
        'aktiv'  => 'aktiv',
    ];

    public function careerLevel(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Milestones relevant for a user: matching their career levels + team (or team-agnostic).
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query
            ->whereIn('career_level_id', $user->careerLevels->pluck('id'))
            ->where(fn (Builder $q) => $q->whereNull('team_id')->orWhere('team_id', $user->team_id));
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
