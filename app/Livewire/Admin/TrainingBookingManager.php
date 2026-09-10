<?php

namespace App\Livewire\Admin;

use App\Models\Coach;
use App\Models\CoachingBooking;
use App\Models\Team;
use App\Models\TrainingBooking;
use App\Models\User;
use App\Services\EmployeeBudgetCategoryService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TrainingBookingManager extends Component
{
    public string $activeTab = 'training'; // 'training' or 'coaching'
    public int $selectedYear;
    public ?int $selectedUserId = null;
    
    // Team Filter (team_id)
    public ?int $teamFilter = null; // null = all teams
    
    // Training Booking Form
    public ?int $editingTrainingId = null;
    public string $trainingName = '';
    public ?string $trainingDate = null;
    public string $trainingStatus = 'pending';
    public ?float $trainingNetCost = null;
    public ?float $trainingCostGross = null;
    public ?float $trainingTravelCosts = null;
    public ?float $trainingAccommodationCosts = null;
    public ?float $trainingOtherCosts = null;
    public ?float $trainingHours = null;
    public bool $trainingDuringWorkHours = true;
    public ?string $trainingWebsite = null;
    public ?string $trainingOrderNumber = null;
    public ?string $trainingNotes = null;
    public bool $trainingClevelApproved = false;
    
    // Coaching Booking Form
    public ?int $editingCoachingId = null;
    public string $coachingCategory = 'employee'; // employee or leadership
    public string $coachingType = 'full';
    public ?string $coachingDate = null;
    public ?string $coachingStartMonth = null;
    public ?string $coachingEndMonth = null;
    public ?int $coachingCoachId = null;
    public ?string $coachingCoachName = null;
    public ?float $coachingHours = null;
    public ?string $coachingNotes = null;
    public bool $coachingClevelApproved = false;
    
    // UI State
    public bool $showTrainingForm = false;
    public bool $showCoachingForm = false;
    public bool $budgetExceeded = false;
    
    protected EmployeeBudgetCategoryService $budgetService;

    protected $rules = [
        'selectedUserId' => 'required|exists:users,id',
        'trainingName' => 'required|string|max:255',
        'trainingDate' => 'nullable|date',
        'trainingStatus' => 'required|string',
        'trainingNetCost' => 'nullable|numeric|min:0',
        'trainingCostGross' => 'nullable|numeric|min:0',
        'trainingTravelCosts' => 'nullable|numeric|min:0',
        'trainingAccommodationCosts' => 'nullable|numeric|min:0',
        'trainingOtherCosts' => 'nullable|numeric|min:0',
        'trainingHours' => 'nullable|numeric|min:0',
        'trainingWebsite' => 'nullable|url|max:500',
        'trainingNotes' => 'nullable|string|max:1000',
        'coachingCategory' => 'required|in:employee,leadership',
        'coachingType' => 'required_if:coachingCategory,employee|in:full,quick_help,leadership',
        'coachingDate' => 'nullable|date',
        'coachingStartMonth' => 'nullable|date',
        'coachingEndMonth' => 'nullable|date|after_or_equal:coachingStartMonth',
        'coachingCoachId' => 'nullable|exists:coaches,id',
        'coachingCoachName' => 'nullable|string|max:255',
        'coachingHours' => 'required_if:coachingCategory,leadership|nullable|numeric|min:0.5',
        'coachingNotes' => 'nullable|string|max:1000',
    ];

    public function boot(EmployeeBudgetCategoryService $budgetService): void
    {
        $this->budgetService = $budgetService;
    }

    public function mount(): void
    {
        $this->selectedYear = (int) date('Y');
        $this->trainingDate = date('Y-m-d');
        $this->coachingDate = date('Y-m-d');
        $this->coachingStartMonth = date('Y-m-01');
        $this->coachingEndMonth = date('Y-m-01');
    }

    public function setTeamFilter(?int $teamId): void
    {
        $this->teamFilter = $teamId;
        $this->selectedUserId = null;
    }

    public function getTeamsProperty(): Collection
    {
        // Get teams that have active users
        return Team::whereHas('users', function ($query) {
            $query->whereNull('archived_at');
        })
        ->withCount(['users' => function ($query) {
            $query->whereNull('archived_at');
        }])
        ->orderBy('name')
        ->get();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetForms();
    }

    public function resetForms(): void
    {
        $this->showTrainingForm = false;
        $this->showCoachingForm = false;
        $this->resetTrainingForm();
        $this->resetCoachingForm();
    }

    public function resetTrainingForm(): void
    {
        $this->editingTrainingId = null;
        $this->trainingName = '';
        $this->trainingDate = date('Y-m-d');
        $this->trainingStatus = 'pending';
        $this->trainingNetCost = null;
        $this->trainingCostGross = null;
        $this->trainingTravelCosts = null;
        $this->trainingAccommodationCosts = null;
        $this->trainingOtherCosts = null;
        $this->trainingHours = null;
        $this->trainingDuringWorkHours = true;
        $this->trainingWebsite = null;
        $this->trainingOrderNumber = null;
        $this->trainingNotes = null;
        $this->trainingClevelApproved = false;
        $this->budgetExceeded = false;
    }

    public function resetCoachingForm(): void
    {
        $this->editingCoachingId = null;
        $this->coachingCategory = 'employee';
        $this->coachingType = 'full';
        $this->coachingDate = date('Y-m-d');
        $this->coachingStartMonth = date('Y-m-01');
        $this->coachingEndMonth = date('Y-m-01');
        $this->coachingCoachId = null;
        $this->coachingCoachName = null;
        $this->coachingHours = null;
        $this->coachingNotes = null;
        $this->coachingClevelApproved = false;
        $this->budgetExceeded = false;
    }

    public function updatedCoachingCategory(): void
    {
        // When switching to leadership, reset type and hours
        if ($this->coachingCategory === 'leadership') {
            $this->coachingType = 'leadership';
            $this->budgetExceeded = false; // Leadership doesn't affect budget
        } else {
            $this->coachingType = 'full';
            $this->coachingHours = null;
            $this->checkBudget();
        }
    }

    public function updatedCoachingHours(): void
    {
        // Recalculate cost display for leadership coaching
        $this->checkBudget();
    }

    public function getCalculatedCoachingCostProperty(): float
    {
        if ($this->coachingCategory === 'leadership') {
            return ($this->coachingHours ?? 0) * CoachingBooking::HOURLY_RATE;
        }
        return CoachingBooking::getCostForType($this->coachingType);
    }

    public function getCoachesProperty(): Collection
    {
        return Coach::active()->orderBy('name')->get();
    }

    public function openTrainingForm(): void
    {
        $this->resetTrainingForm();
        $this->showTrainingForm = true;
    }

    public function openCoachingForm(): void
    {
        $this->resetCoachingForm();
        $this->showCoachingForm = true;
    }

    public function editTraining(int $id): void
    {
        $booking = TrainingBooking::findOrFail($id);
        
        if (!$this->canEdit($booking->user_id)) {
            return;
        }

        $this->editingTrainingId = $booking->id;
        $this->selectedUserId = $booking->user_id;
        $this->trainingName = $booking->name;
        $this->trainingDate = $booking->booking_date?->format('Y-m-d');
        $this->trainingStatus = $booking->status ?? 'pending';
        $this->trainingNetCost = $booking->net_cost;
        $this->trainingCostGross = $booking->cost_gross;
        $this->trainingTravelCosts = $booking->travel_costs;
        $this->trainingAccommodationCosts = $booking->accommodation_costs;
        $this->trainingOtherCosts = $booking->other_costs;
        $this->trainingHours = $booking->hours;
        $this->trainingDuringWorkHours = $booking->during_work_hours ?? true;
        $this->trainingWebsite = $booking->website;
        $this->trainingOrderNumber = $booking->order_number;
        $this->trainingNotes = $booking->notes;
        $this->trainingClevelApproved = $booking->clevel_approved ?? false;
        
        $this->showTrainingForm = true;
        $this->checkBudget();
    }

    public function editCoaching(int $id): void
    {
        $booking = CoachingBooking::findOrFail($id);
        
        if (!$this->canEdit($booking->user_id)) {
            return;
        }

        $this->editingCoachingId = $booking->id;
        $this->selectedUserId = $booking->user_id;
        $this->coachingCategory = $booking->coaching_category ?? 'employee';
        $this->coachingType = $booking->coaching_type;
        $this->coachingDate = $booking->booking_date?->format('Y-m-d');
        $this->coachingStartMonth = $booking->start_month?->format('Y-m-d');
        $this->coachingEndMonth = $booking->end_month?->format('Y-m-d');
        $this->coachingCoachId = $booking->coach_id;
        $this->coachingCoachName = $booking->coach_name;
        $this->coachingHours = $booking->hours;
        $this->coachingNotes = $booking->notes;
        $this->coachingClevelApproved = $booking->clevel_approved ?? false;
        
        $this->showCoachingForm = true;
        $this->checkBudget();
    }

    public function saveTraining(): void
    {
        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'trainingName' => 'required|string|max:255',
        ]);

        if (!$this->canEdit($this->selectedUserId)) {
            return;
        }

        $data = [
            'user_id' => $this->selectedUserId,
            'booked_by_id' => Auth::id(),
            'name' => $this->trainingName,
            'booking_date' => $this->trainingDate,
            'status' => $this->trainingStatus,
            'net_cost' => $this->trainingNetCost,
            'cost_gross' => $this->trainingCostGross,
            'travel_costs' => $this->trainingTravelCosts,
            'accommodation_costs' => $this->trainingAccommodationCosts,
            'other_costs' => $this->trainingOtherCosts,
            'hours' => $this->trainingHours,
            'during_work_hours' => $this->trainingDuringWorkHours,
            'website' => $this->trainingWebsite,
            'order_number' => $this->trainingOrderNumber,
            'notes' => $this->trainingNotes,
            'clevel_approved' => $this->trainingClevelApproved,
        ];

        if ($this->trainingClevelApproved && !$this->editingTrainingId) {
            $data['clevel_approved_by'] = Auth::id();
            $data['clevel_approved_at'] = now();
        }

        if ($this->editingTrainingId) {
            $booking = TrainingBooking::findOrFail($this->editingTrainingId);
            $booking->update($data);
            session()->flash('message', 'Buchung aktualisiert.');
        } else {
            TrainingBooking::create($data);
            session()->flash('message', 'Buchung erstellt.');
        }

        $this->resetForms();
    }

    public function saveCoaching(): void
    {
        $validationRules = [
            'selectedUserId' => 'required|exists:users,id',
            'coachingCategory' => 'required|in:employee,leadership',
        ];

        if ($this->coachingCategory === 'employee') {
            $validationRules['coachingType'] = 'required|in:full,quick_help';
        } else {
            $validationRules['coachingHours'] = 'required|numeric|min:0.5';
        }

        $this->validate($validationRules);

        if (!$this->canEdit($this->selectedUserId)) {
            return;
        }

        $isLeadership = $this->coachingCategory === 'leadership';

        $data = [
            'user_id' => $this->selectedUserId,
            'booked_by_user_id' => Auth::id(),
            'booking_date' => $this->coachingDate,
            'coaching_category' => $this->coachingCategory,
            'coaching_type' => $isLeadership ? 'leadership' : $this->coachingType,
            'hours' => $isLeadership ? $this->coachingHours : null,
            'start_month' => $isLeadership ? $this->coachingStartMonth : null,
            'end_month' => $isLeadership ? $this->coachingEndMonth : null,
            'deduct_from_budget' => !$isLeadership,
            'coach_id' => $this->coachingCoachId,
            'coach_name' => $this->coachingCoachName ?: ($this->coachingCoachId ? Coach::find($this->coachingCoachId)?->name : null),
            'notes' => $this->coachingNotes,
            'clevel_approved' => $this->coachingClevelApproved,
        ];

        // Calculate cost (will be auto-calculated by model, but set explicitly)
        if ($isLeadership) {
            $hourlyRate = $this->coachingCoachId 
                ? Coach::find($this->coachingCoachId)?->hourly_rate ?? CoachingBooking::HOURLY_RATE 
                : CoachingBooking::HOURLY_RATE;
            $data['cost'] = $this->coachingHours * $hourlyRate;
        } else {
            $data['cost'] = CoachingBooking::getCostForType($this->coachingType);
        }

        if ($this->coachingClevelApproved && !$this->editingCoachingId) {
            $data['clevel_approved_by'] = Auth::id();
            $data['clevel_approved_at'] = now();
        }

        if ($this->editingCoachingId) {
            $booking = CoachingBooking::findOrFail($this->editingCoachingId);
            $booking->update($data);
            session()->flash('message', 'Coaching-Buchung aktualisiert.');
        } else {
            CoachingBooking::create($data);
            session()->flash('message', 'Coaching-Buchung erstellt.');
        }

        $this->resetForms();
    }

    public function deleteTraining(int $id): void
    {
        if (!Auth::user()->isAdmin()) {
            return;
        }

        TrainingBooking::findOrFail($id)->delete();
        session()->flash('message', 'Buchung gelöscht.');
    }

    public function deleteCoaching(int $id): void
    {
        if (!Auth::user()->isAdmin()) {
            return;
        }

        CoachingBooking::findOrFail($id)->delete();
        session()->flash('message', 'Coaching-Buchung gelöscht.');
    }

    public function updatedSelectedUserId(): void
    {
        $this->checkBudget();
    }

    public function updatedTrainingNetCost(): void
    {
        $this->checkBudget();
    }

    public function updatedTrainingTravelCosts(): void
    {
        $this->checkBudget();
    }

    public function updatedTrainingAccommodationCosts(): void
    {
        $this->checkBudget();
    }

    public function updatedTrainingOtherCosts(): void
    {
        $this->checkBudget();
    }

    public function updatedCoachingType(): void
    {
        $this->checkBudget();
    }

    protected function checkBudget(): void
    {
        // Leadership coaching doesn't affect budget
        if ($this->activeTab === 'coaching' && $this->coachingCategory === 'leadership') {
            $this->budgetExceeded = false;
            return;
        }

        if (!$this->selectedUserId) {
            $this->budgetExceeded = false;
            return;
        }

        $user = User::find($this->selectedUserId);
        if (!$user) {
            $this->budgetExceeded = false;
            return;
        }

        // Calculate current spending
        $currentSpending = $this->getCurrentSpending($user);
        
        // Calculate new cost
        $newCost = 0;
        if ($this->activeTab === 'training') {
            $newCost = ($this->trainingNetCost ?? 0)
                + ($this->trainingTravelCosts ?? 0)
                + ($this->trainingAccommodationCosts ?? 0)
                + ($this->trainingOtherCosts ?? 0);
        } else {
            // Only employee coaching counts towards budget
            $newCost = CoachingBooking::getCostForType($this->coachingType);
        }

        // Get budget limit
        $effectiveBudget = $this->budgetService->calculateEffectiveBudget($user, $this->selectedYear);
        $maxCash = $effectiveBudget['max_cash'] ?? $effectiveBudget['total_budget'] ?? 3000;

        $this->budgetExceeded = ($currentSpending + $newCost) > $maxCash;
    }

    protected function getCurrentSpending(User $user): float
    {
        $trainingSpending = TrainingBooking::where('user_id', $user->id)
            ->whereYear('created_at', $this->selectedYear)
            ->when($this->editingTrainingId, fn($q) => $q->where('id', '!=', $this->editingTrainingId))
            ->get()
            ->sum(function ($booking) {
                return ($booking->net_cost ?? 0)
                    + ($booking->travel_costs ?? 0)
                    + ($booking->accommodation_costs ?? 0)
                    + ($booking->other_costs ?? 0);
            });

        // Only count coaching that is deducted from budget (not leadership coaching)
        $coachingSpending = CoachingBooking::where('user_id', $user->id)
            ->whereYear('created_at', $this->selectedYear)
            ->where('deduct_from_budget', true)
            ->when($this->editingCoachingId, fn($q) => $q->where('id', '!=', $this->editingCoachingId))
            ->sum('cost');

        return $trainingSpending + $coachingSpending;
    }

    protected function canEdit(int $userId): bool
    {
        $currentUser = Auth::user();
        
        // Admin kann alles
        if ($currentUser->isAdmin()) {
            return true;
        }

        // People Manager kann sein Team bearbeiten
        if ($currentUser->hasPeopleManagerAccess()) {
            $managedUserIds = $currentUser->teamEmployees()->pluck('id')->toArray();
            return in_array($userId, $managedUserIds);
        }

        return false;
    }

    public function getAvailableUsersProperty()
    {
        $currentUser = Auth::user();
        $query = null;

        if ($currentUser->isAdmin()) {
            $query = User::active()->orderBy('name');
        } elseif ($currentUser->hasPeopleManagerAccess()) {
            $query = $currentUser->teamEmployees()->orderBy('name');
        } else {
            return collect();
        }

        // Apply team filter if set (by team_id)
        if ($this->teamFilter) {
            $query->where('team_id', $this->teamFilter);
        }

        return $query->get();
    }

    public function getTrainingBookingsProperty()
    {
        $query = TrainingBooking::with(['user', 'bookedBy'])
            ->whereYear('created_at', $this->selectedYear)
            ->orderByDesc('created_at');

        if (!Auth::user()->isAdmin()) {
            $userIds = Auth::user()->teamEmployees()->pluck('id')->toArray();
            $query->whereIn('user_id', $userIds);
        }

        return $query->get();
    }

    public function getCoachingBookingsProperty()
    {
        $query = CoachingBooking::with(['user', 'bookedBy'])
            ->whereYear('created_at', $this->selectedYear)
            ->orderByDesc('created_at');

        if (!Auth::user()->isAdmin()) {
            $userIds = Auth::user()->teamEmployees()->pluck('id')->toArray();
            $query->whereIn('user_id', $userIds);
        }

        return $query->get();
    }

    public function getStatusOptionsProperty(): array
    {
        return TrainingBooking::getStatusOptions();
    }

    public function getCoachingTypeOptionsProperty(): array
    {
        return CoachingBooking::getTypeOptions();
    }

    public function getCoachingCategoryOptionsProperty(): array
    {
        return CoachingBooking::getCategoryOptions();
    }

    public function render()
    {
        return view('livewire.admin.training-booking-manager', [
            'availableUsers' => $this->availableUsers,
            'trainingBookings' => $this->trainingBookings,
            'coachingBookings' => $this->coachingBookings,
            'statusOptions' => $this->statusOptions,
            'coachingTypeOptions' => $this->coachingTypeOptions,
            'coachingCategoryOptions' => $this->coachingCategoryOptions,
            'teams' => $this->teams,
            'coaches' => $this->coaches,
            'calculatedCoachingCost' => $this->calculatedCoachingCost,
        ])->layout('layouts.app');
    }
}
