<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSessionSeries extends Model
{
    protected $table = 'training_session_series';

    protected $fillable = [
        'module_id',
        'trainer_id',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'frequency',
        'frequency_interval',
        'days_of_week',
        'location',
        'max_participants',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days_of_week' => 'array',
            'frequency_interval' => 'integer',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TrainingSession::class, 'series_id');
    }

    public function upcomingSessions(): HasMany
    {
        return $this->sessions()->where('start_at', '>=', now())->orderBy('start_at');
    }

    public function frequencyLabel(): string
    {
        return match ($this->frequency) {
            'daily' => $this->frequency_interval === 1 ? 'Täglich' : "Alle {$this->frequency_interval} Tage",
            'weekly' => $this->frequency_interval === 1 ? 'Wöchentlich' : "Alle {$this->frequency_interval} Wochen",
            'monthly' => $this->frequency_interval === 1 ? 'Monatlich' : "Alle {$this->frequency_interval} Monate",
            default => 'Benutzerdefiniert',
        };
    }

    public function daysOfWeekLabels(): array
    {
        $map = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];

        return collect($this->days_of_week ?? [])
            ->sort()
            ->map(fn ($day) => $map[$day] ?? $day)
            ->values()
            ->toArray();
    }
}
