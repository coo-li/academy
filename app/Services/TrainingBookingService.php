<?php

namespace App\Services;

use App\Models\TrainingBooking;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TrainingBookingService
{
    protected AsanaService $asanaService;

    public function __construct(AsanaService $asanaService)
    {
        $this->asanaService = $asanaService;
    }

    public function create(array $data): TrainingBooking
    {
        $booking = TrainingBooking::create([
            'user_id' => $data['user_id'],
            'booked_by_id' => Auth::id(),
            'name' => $data['name'],
            'net_cost' => $data['net_cost'],
            'requires_gross_billing' => $data['requires_gross_billing'] ?? false,
            'during_work_hours' => $data['during_work_hours'] ?? false,
            'hours' => $data['hours'] ?? null,
            'notes' => $data['notes'] ?? null,
            'budget_entry_created' => false,
        ]);

        $this->createAsanaTask($booking);

        return $booking;
    }

    protected function createAsanaTask(TrainingBooking $booking): void
    {
        $user = $booking->user;
        $bookedBy = $booking->bookedBy;
        $profileUrl = route('manage.employees.show', $user);

        $name = "Weiterbildung gebucht: {$booking->name} für {$user->name}";

        $noteLines = [
            "=== Weiterbildung gebucht ===",
            "",
            "Mitarbeiter: {$user->name}",
            "E-Mail: {$user->email}",
            "Team: " . ($user->team?->name ?? '–'),
            "",
            "Weiterbildung: {$booking->name}",
            "Nettokosten: " . number_format($booking->net_cost, 2, ',', '.') . " €",
            "Brutto-Abrechnung erforderlich: " . ($booking->requires_gross_billing ? 'Ja' : 'Nein'),
            "",
            "In Arbeitszeit: " . ($booking->during_work_hours ? 'Ja' : 'Nein'),
        ];

        if ($booking->during_work_hours && $booking->hours) {
            $noteLines[] = "Geplante Stunden: " . number_format($booking->hours, 1, ',', '.') . " h";
        }

        $noteLines = array_merge($noteLines, [
            "",
            "Gebucht von: {$bookedBy->name}",
            "Buchungsdatum: " . now()->format('d.m.Y, H:i'),
            "",
            "── Aufgaben ──",
            "",
            "[ ] Budget-Eintrag anlegen",
        ]);

        if ($booking->during_work_hours) {
            $noteLines[] = "[ ] Arbeitszeit als abgegolten markieren";
        }

        $noteLines = array_merge($noteLines, [
            "",
            "Profil-Link: {$profileUrl}",
            "",
            "Diese Task wurde automatisch von der td Academy erstellt.",
            "Bitte als erledigt markieren, sobald der Budget-Eintrag angelegt wurde.",
        ]);

        $notes = implode("\n", $noteLines);

        $task = $this->asanaService->createTask(
            name: $name, 
            notes: $notes, 
            assignee: $bookedBy->email
        );

        if ($task && isset($task['gid'])) {
            $booking->update(['asana_task_gid' => $task['gid']]);
            Log::info('TrainingBooking: Asana task created', [
                'booking_id' => $booking->id,
                'task_gid' => $task['gid'],
            ]);
        }
    }

    public function markBudgetCreated(TrainingBooking $booking): bool
    {
        $booking->update(['budget_entry_created' => true]);

        if ($booking->asana_task_gid) {
            $completed = $this->asanaService->completeTask($booking->asana_task_gid);
            
            Log::info('TrainingBooking: Marked as completed', [
                'booking_id' => $booking->id,
                'asana_completed' => $completed,
            ]);

            return $completed;
        }

        return true;
    }

    public function getPendingBookingsForManager(User $manager)
    {
        return TrainingBooking::with(['user', 'bookedBy'])
            ->where('booked_by_id', $manager->id)
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getAllPendingBookings()
    {
        return TrainingBooking::with(['user', 'bookedBy'])
            ->pending()
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
