<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booked_by_id',
        'name',
        'net_cost',
        'requires_gross_billing',
        'during_work_hours',
        'hours',
        'asana_task_gid',
        'budget_entry_created',
        'notes',
    ];

    protected $casts = [
        'net_cost' => 'decimal:2',
        'hours' => 'decimal:2',
        'requires_gross_billing' => 'boolean',
        'during_work_hours' => 'boolean',
        'budget_entry_created' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_id');
    }

    public function scopePending($query)
    {
        return $query->where('budget_entry_created', false);
    }

    public function scopeCompleted($query)
    {
        return $query->where('budget_entry_created', true);
    }

    public function isPending(): bool
    {
        return !$this->budget_entry_created;
    }

    public function isCompleted(): bool
    {
        return $this->budget_entry_created;
    }
}
