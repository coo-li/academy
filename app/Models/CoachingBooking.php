<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoachingBooking extends Model
{
    // Coaching-Typen mit Festpreisen (für Mitarbeitercoaching)
    public const TYPE_FULL = 'full';           // Volles Coaching-Programm: 1000€
    public const TYPE_QUICK_HELP = 'quick_help'; // Quick Help: 750€
    public const TYPE_LEADERSHIP = 'leadership'; // Leadership Coaching: Stunden × Stundensatz

    // Coaching-Kategorien
    public const CATEGORY_EMPLOYEE = 'employee';     // Mitarbeitercoaching - wird vom Budget abgezogen
    public const CATEGORY_LEADERSHIP = 'leadership'; // Leadership/Head-of Coaching - NICHT vom Budget abgezogen

    // Festpreise
    public const COST_FULL = 1000.00;
    public const COST_QUICK_HELP = 750.00;
    public const HOURLY_RATE = 250.00; // Stundensatz für Leadership Coaching

    protected $fillable = [
        'user_id',
        'booked_by_user_id',
        'booking_date',
        'start_month',
        'end_month',
        'coaching_type',
        'coaching_category',
        'cost',
        'hours',
        'deduct_from_budget',
        'coach_id',
        'coach_name',
        'notes',
        'clevel_approved',
        'clevel_approved_by',
        'clevel_approved_at',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'start_month' => 'date',
        'end_month' => 'date',
        'cost' => 'decimal:2',
        'hours' => 'decimal:2',
        'deduct_from_budget' => 'boolean',
        'clevel_approved' => 'boolean',
        'clevel_approved_at' => 'datetime',
    ];

    /**
     * Der Mitarbeiter, für den das Coaching ist.
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
        return $this->belongsTo(User::class, 'booked_by_user_id');
    }

    /**
     * Wer die C-Level Genehmigung erteilt hat.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'clevel_approved_by');
    }

    /**
     * Der zugewiesene Coach.
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class);
    }

    /**
     * Kosten basierend auf Coaching-Typ berechnen.
     */
    public static function getCostForType(string $type, ?float $hours = null): float
    {
        return match ($type) {
            self::TYPE_FULL => self::COST_FULL,
            self::TYPE_QUICK_HELP => self::COST_QUICK_HELP,
            self::TYPE_LEADERSHIP => ($hours ?? 0) * self::HOURLY_RATE,
            default => 0,
        };
    }

    /**
     * Alle verfügbaren Coaching-Typen für Mitarbeitercoaching.
     */
    public static function getTypeOptions(): array
    {
        return [
            self::TYPE_FULL => 'Volles Coaching-Programm (1.000 €)',
            self::TYPE_QUICK_HELP => 'Coaching Quick Help (750 €)',
        ];
    }

    /**
     * Alle verfügbaren Coaching-Kategorien.
     */
    public static function getCategoryOptions(): array
    {
        return [
            self::CATEGORY_EMPLOYEE => 'Mitarbeitercoaching (vom Budget abgezogen)',
            self::CATEGORY_LEADERSHIP => 'Leadership Coaching (nicht vom Budget abgezogen)',
        ];
    }

    /**
     * Menschenlesbarer Typ.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->coaching_type) {
            self::TYPE_FULL => 'Volles Coaching-Programm',
            self::TYPE_QUICK_HELP => 'Coaching Quick Help',
            self::TYPE_LEADERSHIP => 'Leadership Coaching',
            default => $this->coaching_type,
        };
    }

    /**
     * Menschenlesbare Kategorie.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->coaching_category) {
            self::CATEGORY_EMPLOYEE => 'Mitarbeitercoaching',
            self::CATEGORY_LEADERSHIP => 'Leadership Coaching',
            default => $this->coaching_category,
        };
    }

    /**
     * Ist dies Leadership Coaching?
     */
    public function isLeadership(): bool
    {
        return $this->coaching_category === self::CATEGORY_LEADERSHIP;
    }

    /**
     * Wird vom Budget abgezogen?
     */
    public function isDeductedFromBudget(): bool
    {
        return $this->deduct_from_budget && !$this->isLeadership();
    }

    /**
     * Zeitraum als String (für Leadership Coaching).
     */
    public function getPeriodLabelAttribute(): ?string
    {
        if (!$this->start_month) {
            return null;
        }

        $start = $this->start_month->format('M Y');
        $end = $this->end_month ? $this->end_month->format('M Y') : 'laufend';

        return $start === $end ? $start : "{$start} - {$end}";
    }

    /**
     * Kosten automatisch setzen beim Speichern.
     */
    protected static function booted(): void
    {
        static::saving(function (CoachingBooking $booking) {
            // Leadership Coaching: Kosten aus Stunden berechnen
            if ($booking->coaching_category === self::CATEGORY_LEADERSHIP) {
                $booking->coaching_type = self::TYPE_LEADERSHIP;
                $booking->deduct_from_budget = false;
                if ($booking->hours) {
                    $hourlyRate = $booking->coach?->hourly_rate ?? self::HOURLY_RATE;
                    $booking->cost = $booking->hours * $hourlyRate;
                }
            } else {
                // Mitarbeitercoaching: Festpreis
                $booking->deduct_from_budget = true;
                if ($booking->isDirty('coaching_type') || !$booking->cost) {
                    $booking->cost = self::getCostForType($booking->coaching_type);
                }
            }
        });
    }

    /**
     * Scope für Mitarbeitercoaching (wird vom Budget abgezogen).
     */
    public function scopeEmployee($query)
    {
        return $query->where('coaching_category', self::CATEGORY_EMPLOYEE);
    }

    /**
     * Scope für Leadership Coaching (nicht vom Budget abgezogen).
     */
    public function scopeLeadership($query)
    {
        return $query->where('coaching_category', self::CATEGORY_LEADERSHIP);
    }

    /**
     * Scope für Budget-relevante Buchungen.
     */
    public function scopeDeductible($query)
    {
        return $query->where('deduct_from_budget', true);
    }
}
