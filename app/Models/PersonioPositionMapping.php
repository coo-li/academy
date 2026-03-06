<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonioPositionMapping extends Model
{
    protected $fillable = [
        'personio_position',
        'personio_level_raw',
        'personio_path_raw',
        'career_path_id',
        'career_level_id',
        'is_auto_matched',
    ];

    protected function casts(): array
    {
        return [
            'is_auto_matched' => 'boolean',
        ];
    }

    public function careerPath(): BelongsTo
    {
        return $this->belongsTo(CareerPath::class);
    }

    public function careerLevel(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class);
    }

    public function isMapped(): bool
    {
        return $this->career_path_id !== null && $this->career_level_id !== null;
    }
}
