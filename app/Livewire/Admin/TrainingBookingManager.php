<?php

namespace App\Livewire\Admin;

use App\Models\Team;
use App\Models\TrainingBooking;
use App\Models\User;
use App\Services\TrainingBookingService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TrainingBookingManager extends Component
{
    public ?int $selectedTeamId = null;
    public ?int $userId = null;
    public string $name = '';
    public ?float $netCost = null;
    public bool $requiresGrossBilling = false;
    public bool $duringWorkHours = false;
    public ?float $hours = null;
    public string $notes = '';

    protected TrainingBookingService $bookingService;

    protected $rules = [
        'userId' => 'required|exists:users,id',
        'name' => 'required|string|max:255',
        'netCost' => 'required|numeric|min:0',
        'requiresGrossBilling' => 'boolean',
        'duringWorkHours' => 'boolean',
        'hours' => 'nullable|numeric|min:0|required_if:duringWorkHours,true',
        'notes' => 'nullable|string|max:1000',
    ];

    protected $messages = [
        'userId.required' => 'Bitte wähle einen Mitarbeiter aus.',
        'name.required' => 'Bitte gib den Namen der Weiterbildung ein.',
        'netCost.required' => 'Bitte gib die Nettokosten ein.',
        'netCost.min' => 'Die Kosten müssen mindestens 0 sein.',
        'hours.required_if' => 'Bitte gib die Stunden ein, wenn die Weiterbildung in Arbeitszeit stattfindet.',
    ];

    public function boot(TrainingBookingService $bookingService): void
    {
        $this->bookingService = $bookingService;
    }

    public function updatedSelectedTeamId(): void
    {
        $this->userId = null;
    }

    public function createBooking(): void
    {
        $this->validate();

        $this->bookingService->create([
            'user_id' => $this->userId,
            'name' => $this->name,
            'net_cost' => $this->netCost,
            'requires_gross_billing' => $this->requiresGrossBilling,
            'during_work_hours' => $this->duringWorkHours,
            'hours' => $this->duringWorkHours ? $this->hours : null,
            'notes' => $this->notes ?: null,
        ]);

        $this->reset(['userId', 'name', 'netCost', 'requiresGrossBilling', 'duringWorkHours', 'hours', 'notes']);

        session()->flash('success', 'Weiterbildung wurde erfolgreich eingebucht. Eine Asana-Task wurde erstellt.');
    }

    public function markComplete(int $bookingId): void
    {
        $booking = TrainingBooking::findOrFail($bookingId);

        if ($booking->booked_by_id !== Auth::id() && !Auth::user()->hasAdminAccess()) {
            session()->flash('error', 'Du kannst nur eigene Buchungen als erledigt markieren.');
            return;
        }

        $this->bookingService->markBudgetCreated($booking);
        
        session()->flash('success', 'Buchung wurde als erledigt markiert. Die Asana-Task wurde geschlossen.');
    }

    public function getTeamsProperty()
    {
        $user = Auth::user();

        if ($user->hasAdminAccess()) {
            return Team::orderBy('name')->get();
        }

        $teamIds = User::where('head_of_user_id', $user->id)
            ->whereNull('archived_at')
            ->whereNotNull('team_id')
            ->pluck('team_id')
            ->unique();

        return Team::whereIn('id', $teamIds)->orderBy('name')->get();
    }

    public function getEmployeesProperty()
    {
        $user = Auth::user();

        $query = User::whereNull('archived_at')->orderBy('name');

        if (!$user->hasAdminAccess()) {
            $query->where('head_of_user_id', $user->id);
        }

        if ($this->selectedTeamId) {
            $query->where('team_id', $this->selectedTeamId);
        }

        return $query->get(['id', 'name', 'email', 'team_id']);
    }

    public function getPendingBookingsProperty()
    {
        $user = Auth::user();

        if ($user->hasAdminAccess()) {
            return $this->bookingService->getAllPendingBookings();
        }

        return $this->bookingService->getPendingBookingsForManager($user);
    }

    public function getCompletedBookingsProperty()
    {
        $user = Auth::user();

        $query = TrainingBooking::with(['user', 'user.team', 'bookedBy'])
            ->where('budget_entry_created', true)
            ->orderBy('created_at', 'desc');

        if (!$user->hasAdminAccess()) {
            $query->where('booked_by_id', $user->id);
        }

        return $query->limit(20)->get();
    }

    public function getAllBookingsProperty()
    {
        $user = Auth::user();

        $query = TrainingBooking::with(['user', 'user.team', 'bookedBy'])
            ->orderBy('created_at', 'desc');

        if (!$user->hasAdminAccess()) {
            $query->where('booked_by_id', $user->id);
        }

        return $query->get();
    }

    public function getIsAdminProperty(): bool
    {
        return Auth::user()->hasAdminAccess();
    }

    public function render()
    {
        return view('livewire.admin.training-booking-manager', [
            'teams' => $this->teams,
            'employees' => $this->employees,
            'pendingBookings' => $this->pendingBookings,
            'completedBookings' => $this->completedBookings,
            'isAdmin' => $this->isAdmin,
        ])->layout('layouts.app');
    }
}
