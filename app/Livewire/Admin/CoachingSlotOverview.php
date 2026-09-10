<?php

namespace App\Livewire\Admin;

use App\Models\Coach;
use App\Models\CoachingBooking;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Component;

class CoachingSlotOverview extends Component
{
    public int $year;
    public array $months = [];
    public string $categoryFilter = 'all'; // all, employee, leadership

    public function mount(): void
    {
        $this->year = now()->year;
        $this->generateMonths();
    }

    protected function generateMonths(): void
    {
        $this->months = [];
        for ($m = 1; $m <= 12; $m++) {
            $this->months[$m] = Carbon::create($this->year, $m, 1)->translatedFormat('M');
        }
    }

    public function previousYear(): void
    {
        $this->year--;
        $this->generateMonths();
    }

    public function nextYear(): void
    {
        $this->year++;
        $this->generateMonths();
    }

    public function getCoachesProperty(): Collection
    {
        return Coach::active()
            ->orderBy('name')
            ->get();
    }

    public function getBookingsProperty(): Collection
    {
        $query = CoachingBooking::with(['user', 'coach'])
            ->whereYear('booking_date', $this->year);

        if ($this->categoryFilter === 'employee') {
            $query->where('coaching_category', CoachingBooking::CATEGORY_EMPLOYEE);
        } elseif ($this->categoryFilter === 'leadership') {
            $query->where('coaching_category', CoachingBooking::CATEGORY_LEADERSHIP);
        }

        return $query->get();
    }

    /**
     * Holt die Buchungen für einen bestimmten Coach und Monat.
     */
    public function getBookingsForCoachMonth(int $coachId, int $month): Collection
    {
        return $this->bookings
            ->filter(function ($booking) use ($coachId, $month) {
                if ($booking->coach_id !== $coachId) {
                    return false;
                }

                // Prüfe ob der Monat im Zeitraum liegt (start_month bis end_month)
                // oder ob booking_date in diesem Monat ist
                $targetDate = Carbon::create($this->year, $month, 1);
                
                if ($booking->start_month && $booking->end_month) {
                    return $targetDate->between(
                        $booking->start_month->startOfMonth(),
                        $booking->end_month->endOfMonth()
                    );
                }
                
                if ($booking->start_month) {
                    return $targetDate->greaterThanOrEqualTo($booking->start_month->startOfMonth());
                }

                return $booking->booking_date->month === $month;
            });
    }

    /**
     * Prüft ob ein Slot belegt ist (für visuelle Darstellung).
     */
    public function getSlotData(int $coachId, int $month): array
    {
        $bookings = $this->getBookingsForCoachMonth($coachId, $month);
        $coach = $this->coaches->firstWhere('id', $coachId);
        $slotsPerMonth = $coach?->slots_per_month ?? 4;

        $slots = [];
        $bookingIndex = 0;
        $bookingsArray = $bookings->values();

        for ($i = 0; $i < $slotsPerMonth; $i++) {
            if (isset($bookingsArray[$bookingIndex])) {
                $booking = $bookingsArray[$bookingIndex];
                $slots[] = [
                    'filled' => true,
                    'user_name' => $booking->user?->name ?? 'Unbekannt',
                    'category' => $booking->coaching_category,
                    'type' => $booking->coaching_type,
                    'booking_id' => $booking->id,
                ];
                $bookingIndex++;
            } else {
                $slots[] = [
                    'filled' => false,
                    'user_name' => null,
                    'category' => null,
                    'type' => null,
                    'booking_id' => null,
                ];
            }
        }

        return $slots;
    }

    /**
     * Zusammenfassung für einen Coach und Monat.
     */
    public function getMonthSummary(int $coachId, int $month): array
    {
        $bookings = $this->getBookingsForCoachMonth($coachId, $month);
        $coach = $this->coaches->firstWhere('id', $coachId);
        $slotsPerMonth = $coach?->slots_per_month ?? 4;

        return [
            'booked' => $bookings->count(),
            'available' => max(0, $slotsPerMonth - $bookings->count()),
            'total' => $slotsPerMonth,
        ];
    }

    public function render()
    {
        return view('livewire.admin.coaching-slot-overview')
            ->layout('components.layouts.admin');
    }
}
