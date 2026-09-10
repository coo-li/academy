<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coach extends Model
{
    public const DEFAULT_SLOTS_PER_MONTH = 4;
    public const DEFAULT_HOURLY_RATE = 250.00;

    protected $fillable = [
        'name',
        'slots_per_month',
        'hourly_rate',
        'is_active',
        'description',
    ];

    protected $casts = [
        'slots_per_month' => 'integer',
        'hourly_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Alle Coaching-Buchungen dieses Coaches.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(CoachingBooking::class);
    }

    /**
     * Nur aktive Coaches.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Anzahl verfügbarer Slots für einen bestimmten Monat.
     */
    public function getAvailableSlotsForMonth(int $year, int $month): int
    {
        $bookedSlots = $this->bookings()
            ->whereYear('booking_date', $year)
            ->whereMonth('booking_date', $month)
            ->count();

        return max(0, $this->slots_per_month - $bookedSlots);
    }

    /**
     * Prüfen ob ein Slot für einen Monat verfügbar ist.
     */
    public function hasAvailableSlot(int $year, int $month): bool
    {
        return $this->getAvailableSlotsForMonth($year, $month) > 0;
    }
}
