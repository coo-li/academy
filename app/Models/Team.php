<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    protected $fillable = [
        'name',
        'personio_department',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function managers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'manager_team')->withTimestamps();
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }
}
