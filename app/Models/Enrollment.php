<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'module_id',
        'training_session_id',
        'status',
        'asana_task_gid',
        'completed_at',
        'cancelled_at',
        'attendance_confirmed_at',
        'attendance_confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'attendance_confirmed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attendance_confirmed_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isAttended(): bool
    {
        return $this->status === 'attended';
    }

    public function isQuizUnlocked(): bool
    {
        if ($this->status === 'attended') {
            return true;
        }

        if ($this->status === 'enrolled' && $this->module?->method?->isSelfStudy()) {
            return true;
        }

        return false;
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['enrolled', 'attended']);
    }

    public function isRequested(): bool
    {
        return $this->status === 'requested';
    }
}
