<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerLevelRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'career_level_id',
        'hourly_rate',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate' => 'decimal:2',
        ];
    }

    public function careerLevel(): BelongsTo
    {
        return $this->belongsTo(CareerLevel::class);
    }
}
