<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CareerLevel extends Model
{
    protected $fillable = [
        'career_path_id',
        'level_number',
        'title',
        'description',
    ];

    public function careerPath(): BelongsTo
    {
        return $this->belongsTo(CareerPath::class);
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class)->orderBy('sort_order');
    }

    public function rate(): HasOne
    {
        return $this->hasOne(CareerLevelRate::class);
    }

    public function getHourlyRateAttribute(): ?float
    {
        return $this->rate?->hourly_rate;
    }

    public function getNameAttribute(): string
    {
        return $this->title ?? '';
    }
}
