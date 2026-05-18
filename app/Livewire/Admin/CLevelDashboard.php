<?php

namespace App\Livewire\Admin;

use App\Models\BudgetEntry;
use App\Models\GlobalBudget;
use App\Models\Team;
use App\Models\TrainingBooking;
use App\Models\User;
use App\Services\BudgetDashboardService;
use Livewire\Component;

class CLevelDashboard extends Component
{
    public int $selectedYear;
    public array $availableYears = [];
    
    public bool $showBudgetModal = false;
    public array $plannedBudgets = [];

    protected BudgetDashboardService $dashboardService;

    public function boot(BudgetDashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    public function mount(): void
    {
        $this->availableYears = $this->dashboardService->getAvailableYears();
        $this->selectedYear = $this->availableYears[0] ?? date('Y');
        $this->loadPlannedBudgets();
    }

    public function loadPlannedBudgets(): void
    {
        $teams = Team::orderBy('name')->get();
        $this->plannedBudgets = [];
        
        foreach ($teams as $team) {
            $serviceDev = GlobalBudget::where('team_id', $team->id)
                ->where('year', $this->selectedYear)
                ->where('category', 'service_development')
                ->first();
            
            $this->plannedBudgets[$team->id] = [
                'team_name' => $team->name,
                'service_dev' => $serviceDev?->amount_planned ?? 0,
            ];
        }
    }

    public function openBudgetModal(): void
    {
        $this->loadPlannedBudgets();
        $this->showBudgetModal = true;
    }

    public function savePlannedBudgets(): void
    {
        foreach ($this->plannedBudgets as $teamId => $budgets) {
            if (isset($budgets['service_dev'])) {
                GlobalBudget::updateOrCreate(
                    [
                        'team_id' => $teamId,
                        'year' => $this->selectedYear,
                        'category' => 'service_development',
                    ],
                    [
                        'budget_type' => 'planned',
                        'amount_planned' => (float) $budgets['service_dev'],
                    ]
                );
            }
        }
        
        $this->showBudgetModal = false;
        $this->dispatch('budgets-saved');
    }

    public function updatedSelectedYear(): void
    {
        $this->loadPlannedBudgets();
    }

    public function getTeamsProperty()
    {
        return Team::with('users')->orderBy('name')->get();
    }

    public function getCompanyBudgetSummaryProperty(): array
    {
        $allUserIds = User::pluck('id');
        
        // Nur persönliche Ziele zählen zum Weiterbildungsbudget
        $personalGoalEntries = BudgetEntry::whereIn('user_id', $allUserIds)
            ->where('type', BudgetEntry::TYPE_PERSONAL_GOAL)
            ->whereYear('date', $this->selectedYear)
            ->get();

        // Externe Schulungen (TrainingBookings)
        $trainingBookings = TrainingBooking::whereIn('user_id', $allUserIds)
            ->whereYear('created_at', $this->selectedYear)
            ->get();

        $employeeCount = User::count();
        $totalBudget = $employeeCount * 3000;
        
        $personalGoalsSpent = $personalGoalEntries->sum('amount');
        $trainingCosts = $trainingBookings->sum('net_cost');
        $totalSpent = $personalGoalsSpent + $trainingCosts;

        return [
            'total_budget' => $totalBudget,
            'total_spent' => $totalSpent,
            'personal_goals_spent' => $personalGoalsSpent,
            'training_costs' => $trainingCosts,
            'training_count' => $trainingBookings->count(),
            'remaining' => $totalBudget - $totalSpent,
            'percentage' => $totalBudget > 0 ? ($totalSpent / $totalBudget) * 100 : 0,
            'employee_count' => $employeeCount,
            'team_count' => Team::count(),
        ];
    }

    public function getServiceDevelopmentSummaryProperty(): array
    {
        $allUserIds = User::pluck('id');
        
        // Team & Service Development = Teamziele + Interne Schulungen + Sonstiges
        $teamGoals = BudgetEntry::whereIn('user_id', $allUserIds)
            ->where('type', BudgetEntry::TYPE_TEAM_GOAL)
            ->whereYear('date', $this->selectedYear)
            ->sum('amount');

        $internalTraining = BudgetEntry::whereIn('user_id', $allUserIds)
            ->where('type', BudgetEntry::TYPE_INTERNAL_TRAINING)
            ->whereYear('date', $this->selectedYear)
            ->sum('amount');

        $other = BudgetEntry::whereIn('user_id', $allUserIds)
            ->where('type', BudgetEntry::TYPE_OTHER_INTERNAL)
            ->whereYear('date', $this->selectedYear)
            ->sum('amount');

        $totalSpent = $teamGoals + $internalTraining + $other;

        // Planbudget aus GlobalBudget laden (Summe aller Teams)
        $plannedBudget = GlobalBudget::where('year', $this->selectedYear)
            ->where('category', 'service_development')
            ->sum('amount_planned');

        return [
            'team_goals' => $teamGoals,
            'internal_training' => $internalTraining,
            'other' => $other,
            'total_spent' => $totalSpent,
            'planned_budget' => $plannedBudget,
            'remaining' => $plannedBudget - $totalSpent,
            'percentage' => $plannedBudget > 0 ? ($totalSpent / $plannedBudget) * 100 : 0,
        ];
    }

    public function getTeamSummariesProperty()
    {
        return $this->teams->map(function ($team) {
            $userIds = $team->users->pluck('id');
            
            // Weiterbildung (Persönliche Ziele + Externe Schulungen)
            $personalGoalsSpent = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_PERSONAL_GOAL)
                ->whereYear('date', $this->selectedYear)
                ->sum('amount');

            $trainingCosts = TrainingBooking::whereIn('user_id', $userIds)
                ->whereYear('created_at', $this->selectedYear)
                ->sum('net_cost');

            $weiterbildungBudget = $team->users->count() * 3000;
            $weiterbildungSpent = $personalGoalsSpent + $trainingCosts;
            $weiterbildungPercentage = $weiterbildungBudget > 0 ? ($weiterbildungSpent / $weiterbildungBudget) * 100 : 0;

            // Team & Service Development (Teamziele + Interne Schulungen + Sonstiges)
            $teamGoals = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_TEAM_GOAL)
                ->whereYear('date', $this->selectedYear)
                ->sum('amount');

            $internalTraining = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_INTERNAL_TRAINING)
                ->whereYear('date', $this->selectedYear)
                ->sum('amount');

            $other = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_OTHER_INTERNAL)
                ->whereYear('date', $this->selectedYear)
                ->sum('amount');

            $serviceDevSpent = $teamGoals + $internalTraining + $other;
            
            // Planbudget aus GlobalBudget laden
            $serviceDevBudget = GlobalBudget::where('team_id', $team->id)
                ->where('year', $this->selectedYear)
                ->where('category', 'service_development')
                ->value('amount_planned') ?? 0;
            $serviceDevPercentage = $serviceDevBudget > 0 ? ($serviceDevSpent / $serviceDevBudget) * 100 : 0;

            return [
                'team' => $team,
                'team_id' => $team->id,
                'team_name' => $team->name,
                'employee_count' => $team->users->count(),
                // Weiterbildung
                'weiterbildung' => [
                    'budget' => $weiterbildungBudget,
                    'spent' => $weiterbildungSpent,
                    'percentage' => round($weiterbildungPercentage, 0),
                    'ampel' => $this->dashboardService->getAmpelStatus($weiterbildungPercentage),
                ],
                // Team & Service Development
                'service_dev' => [
                    'budget' => $serviceDevBudget,
                    'spent' => $serviceDevSpent,
                    'percentage' => round($serviceDevPercentage, 0),
                ],
            ];
        });
    }

    public function render()
    {
        return view('livewire.admin.c-level-dashboard', [
            'teams' => $this->teams,
            'companyBudgetSummary' => $this->companyBudgetSummary,
            'serviceDevelopmentSummary' => $this->serviceDevelopmentSummary,
            'teamSummaries' => $this->teamSummaries,
        ])->layout('layouts.app');
    }
}
