<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonioSyncLog extends Model
{
    protected $fillable = [
        'status',
        'employees_fetched',
        'users_created',
        'users_updated',
        'users_skipped',
        'error_message',
        'details',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function duration(): ?string
    {
        if (! $this->started_at || ! $this->finished_at) {
            return null;
        }

        $seconds = $this->started_at->diffInSeconds($this->finished_at);

        return $seconds < 60
            ? "{$seconds}s"
            : round($seconds / 60, 1) . 'min';
    }
}
