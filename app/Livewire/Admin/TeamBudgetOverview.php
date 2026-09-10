<?php

namespace App\Livewire\Admin;

use App\Models\BudgetEntry;
use App\Models\Team;
use App\Models\TrainingBooking;
use App\Services\BudgetDashboardService;
use App\Services\EmployeeBudgetCategoryService;
use Livewire\Component;

class TeamBudgetOverview extends Component
{
    public int $selectedYear;
    public int $teamId;
    public string $activeTab = 'uebersicht';
    public array $availableYears = [];

    protected BudgetDashboardService $dashboardService;
    protected EmployeeBudgetCategoryService $budgetCategoryService;

    public function boot(BudgetDashboardService $dashboardService, EmployeeBudgetCategoryService $budgetCategoryService): void
    {
        $this->dashboardService = $dashboardService;
        $this->budgetCategoryService = $budgetCategoryService;
    }

    public function mount(int $teamId): void
    {
        $this->teamId = $teamId;
        $this->availableYears = $this->dashboardService->getAvailableYears();
        $this->selectedYear = $this->availableYears[0] ?? (int) date('Y');
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function getTeamProperty(): Team
    {
        return Team::with('users')->findOrFail($this->teamId);
    }

    public function getTeamBudgetSummaryProperty(): array
    {
        $team = $this->team;
        $userIds = $team->users->pluck('id');
        
        // Nur persönliche Ziele zählen zum Weiterbildungsbudget
        $personalGoalEntries = BudgetEntry::whereIn('user_id', $userIds)
            ->where('type', BudgetEntry::TYPE_PERSONAL_GOAL)
            ->whereYear('date', $this->selectedYear)
            ->get();

        // Externe Schulungen (TrainingBookings) - echte Kosten
        $trainingBookings = TrainingBooking::whereIn('user_id', $userIds)
            ->whereYear('created_at', $this->selectedYear)
            ->get();

        $totalBudget = $this->dashboardService->calculateTotalBudgetForUsers($team->users);
        
        // Nur persönliche Ziele + externe Schulungen vom Budget abziehen
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
            'employee_count' => $team->users->count(),
        ];
    }
    
    public function getTrainingBookingsProperty()
    {
        $userIds = $this->team->users->pluck('id');
        
        return TrainingBooking::whereIn('user_id', $userIds)
            ->whereYear('created_at', $this->selectedYear)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getEntriesByTypeProperty(): array
    {
        $team = $this->team;
        $userIds = $team->users->pluck('id');

        $getGroupedEntries = function($type) use ($userIds) {
            $entries = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', $type)
                ->whereYear('date', $this->selectedYear)
                ->with('user')
                ->orderBy('date', 'desc')
                ->get();

            // Gruppiere nach label (einzelne Budget-Posten) statt budget_name (Projekt-Ebene)
            $grouped = $entries->groupBy('label');
            
            $result = [];
            foreach ($grouped as $label => $items) {
                $totalAmount = $items->sum('amount');
                $result[] = [
                    'budget_name' => $label ?: 'Unbenannt',
                    'total_amount' => $totalAmount,
                    'entries' => $items,
                    'users' => $items->pluck('user')->unique('id')->values(),
                ];
            }
            
            return collect($result)->sortByDesc('total_amount')->values();
        };

        return [
            'personal_goals' => $getGroupedEntries(BudgetEntry::TYPE_PERSONAL_GOAL),
            'team_goals' => $getGroupedEntries(BudgetEntry::TYPE_TEAM_GOAL),
            'internal_training' => $getGroupedEntries(BudgetEntry::TYPE_INTERNAL_TRAINING),
            'other' => $getGroupedEntries(BudgetEntry::TYPE_OTHER_INTERNAL),
        ];
    }

    public function getCategoryStatsProperty(): array
    {
        $entries = $this->entriesByType;
        
        $getStats = function($items) {
            $totalAmount = $items->sum('total_amount');
            return [
                'count' => $items->count(),
                'total_amount' => $totalAmount,
            ];
        };
        
        return [
            'personal_goals' => $getStats($entries['personal_goals']),
            'team_goals' => $getStats($entries['team_goals']),
            'internal_training' => $getStats($entries['internal_training']),
            'other' => $getStats($entries['other']),
        ];
    }

    public function getMonthlyBreakdownProperty(): array
    {
        $team = $this->team;
        $userIds = $team->users->pluck('id');

        $getBreakdownForType = function($type) use ($userIds) {
            $entries = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', $type)
                ->whereYear('date', $this->selectedYear)
                ->with('user')
                ->get();

            // Gruppiere nach label (einzelne Budget-Posten) statt budget_name (Projekt-Ebene)
            $grouped = $entries->groupBy('label');
            
            $goals = [];
            $monthTotals = array_fill(1, 12, ['ist' => 0, 'soll' => 0]);
            
            foreach ($grouped as $label => $items) {
                $goal = [
                    'name' => $label ?: 'Unbenannt',
                    'category' => $items->first()?->goal_category,
                    'months' => [],
                    'total_ist' => 0,
                    'total_soll' => 0,
                    'users' => $items->pluck('user')->unique('id')->map(fn($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                    ])->values()->toArray(),
                ];
                
                for ($month = 1; $month <= 12; $month++) {
                    $monthEntries = $items->filter(fn($e) => $e->date->month === $month);
                    
                    // Ist-Werte (budget_type = 'used')
                    $istAmount = $monthEntries->where('budget_type', BudgetEntry::BUDGET_TYPE_USED)->sum('amount');
                    // Soll-Werte (budget_type = 'available')
                    $sollAmount = $monthEntries->where('budget_type', BudgetEntry::BUDGET_TYPE_AVAILABLE)->sum('amount');
                    
                    $goal['months'][$month] = [
                        'ist' => round($istAmount, 2),
                        'soll' => round($sollAmount, 2),
                        'users' => $monthEntries->pluck('user')->unique('id')->map(fn($u) => [
                            'id' => $u->id,
                            'name' => $u->name,
                        ])->values()->toArray(),
                    ];
                    
                    $goal['total_ist'] += $istAmount;
                    $goal['total_soll'] += $sollAmount;
                    $monthTotals[$month]['ist'] += $istAmount;
                    $monthTotals[$month]['soll'] += $sollAmount;
                }
                
                $goal['total_ist'] = round($goal['total_ist'], 2);
                $goal['total_soll'] = round($goal['total_soll'], 2);
                $goals[] = $goal;
            }
            
            // Sortiere nach Ist-Werten (höchste zuerst)
            usort($goals, fn($a, $b) => $b['total_ist'] <=> $a['total_ist']);
            
            $totalIst = array_sum(array_column($monthTotals, 'ist'));
            $totalSoll = array_sum(array_column($monthTotals, 'soll'));
            
            return [
                'goals' => $goals,
                'month_totals' => $monthTotals,
                'total_ist' => round($totalIst, 2),
                'total_soll' => round($totalSoll, 2),
            ];
        };
        
        return [
            'personal_goals' => $getBreakdownForType(BudgetEntry::TYPE_PERSONAL_GOAL),
            'team_goals' => $getBreakdownForType(BudgetEntry::TYPE_TEAM_GOAL),
            'internal_training' => $getBreakdownForType(BudgetEntry::TYPE_INTERNAL_TRAINING),
            'other' => $getBreakdownForType(BudgetEntry::TYPE_OTHER_INTERNAL),
        ];
    }

    public function render()
    {
        return view('livewire.admin.team-budget-overview', [
            'team' => $this->team,
            'teamBudgetSummary' => $this->teamBudgetSummary,
            'entriesByType' => $this->entriesByType,
            'categoryStats' => $this->categoryStats,
            'monthlyBreakdown' => $this->monthlyBreakdown,
            'trainingBookings' => $this->trainingBookings,
        ])->layout('layouts.app');
    }
}
