<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingBooking extends Model
{
    // Status-Konstanten
    public const STATUS_PENDING = 'pending';
    public const STATUS_ORDERED = 'ordered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'booked_by_id',          // Existing column name
        'name',                   // Existing column name (= description)
        'booking_date',
        'status',
        'net_cost',              // Existing column name
        'cost_gross',
        'travel_costs',
        'accommodation_costs',
        'other_costs',
        'hours',                 // Existing column name (= working_hours)
        'during_work_hours',
        'requires_gross_billing',
        'asana_task_gid',
        'budget_entry_created',
        'website',
        'order_number',
        'notes',
        'clevel_approved',
        'clevel_approved_by',
        'clevel_approved_at',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'net_cost' => 'decimal:2',
        'cost_gross' => 'decimal:2',
        'travel_costs' => 'decimal:2',
        'accommodation_costs' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'hours' => 'decimal:2',
        'during_work_hours' => 'boolean',
        'requires_gross_billing' => 'boolean',
        'budget_entry_created' => 'boolean',
        'clevel_approved' => 'boolean',
        'clevel_approved_at' => 'datetime',
    ];

    /**
     * Der Mitarbeiter, für den die Buchung ist.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Wer die Buchung erstellt hat (People Manager).
     */
    public function bookedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'booked_by_id');
    }

    /**
     * Wer die C-Level Genehmigung erteilt hat.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clevel_approved_by');
    }

    /**
     * Gesamtkosten (alle Posten zusammen, ohne Arbeitszeit).
     */
    public function getTotalCostAttribute(): float
    {
        return ($this->net_cost ?? 0)
            + ($this->travel_costs ?? 0)
            + ($this->accommodation_costs ?? 0)
            + ($this->other_costs ?? 0);
    }

    /**
     * Gesamtkosten inkl. Arbeitszeit-Kosten.
     */
    public function getTotalCostWithTimeAttribute(): float
    {
        $hourlyRate = $this->user?->getHourlyRate() ?? 50;
        $timeCost = ($this->hours ?? 0) * $hourlyRate;
        
        return $this->total_cost + $timeCost;
    }

    /**
     * Alias: Beschreibung (für Formular).
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->name;
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['name'] = $value;
    }

    /**
     * Alle verfügbaren Status-Optionen.
     */
    public static function getStatusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Ausstehend',
            self::STATUS_ORDERED => 'Bestellt',
            self::STATUS_COMPLETED => 'Erledigt',
            self::STATUS_ON_HOLD => 'On Hold',
            self::STATUS_CANCELLED => 'Storniert',
        ];
    }

    /**
     * Menschenlesbarer Status.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }
}
