<?php

namespace App\Services;

use App\Models\BudgetEntry;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;

class BudgetDashboardService
{
    public const AMPEL_GREEN = 'green';
    public const AMPEL_YELLOW = 'yellow';
    public const AMPEL_RED = 'red';
    public const AMPEL_ORANGE = 'orange';
    public const AMPEL_GRAY = 'gray';

    public const THRESHOLD_OVER_BUDGET = 110;
    public const THRESHOLD_GREEN = 90;
    public const THRESHOLD_YELLOW = 60;

    public function getTeamOverviewForYear(int $year, ?int $teamId = null): Collection
    {
        $query = Team::with(['users.careerLevel.rate', 'globalBudgets']);
        
        if ($teamId) {
            $query->where('id', $teamId);
        }

        return $query->get()->map(function (Team $team) use ($year) {
            $plannedHours = $team->getPlannedHoursForYear($year);
            $actualHours = $team->getActualHoursForYear($year);
            $utilizationRate = $plannedHours > 0 
                ? ($actualHours / $plannedHours) * 100 
                : 0;

            $opportunityCost = $this->calculateOpportunityCost($team, $year, $plannedHours, $actualHours);

            return [
                'team' => $team,
                'team_id' => $team->id,
                'team_name' => $team->name,
                'planned_hours' => round($plannedHours, 2),
                'actual_hours' => round($actualHours, 2),
                'utilization_rate' => round($utilizationRate, 1),
                'opportunity_cost' => round($opportunityCost, 2),
                'employee_count' => $team->users->count(),
                'ampel' => $this->getAmpelStatus($utilizationRate),
            ];
        });
    }

    public function getSubordinateBudgetOverview(User $manager, int $year): Collection
    {
        return $manager->subordinates()
            ->with(['careerLevel.rate', 'budgetEntries' => function ($query) use ($year) {
                $query->whereYear('date', $year);
            }, 'userBudgets' => function ($query) use ($year) {
                $query->where('year', $year);
            }])
            ->get()
            ->map(function (User $employee) use ($year) {
                $targetHours = $employee->target_hours_per_year ?? 0;
                $actualHours = $this->calculateTotalHoursForUser($employee, $year);
                $utilizationRate = $targetHours > 0 
                    ? ($actualHours / $targetHours) * 100 
                    : 0;

                $budgetBreakdown = $this->getUserBudgetBreakdown($employee, $year);

                return [
                    'user' => $employee,
                    'user_id' => $employee->id,
                    'user_name' => $employee->name,
                    'target_hours' => round($targetHours, 2),
                    'actual_hours' => round($actualHours, 2),
                    'utilization_rate' => round($utilizationRate, 1),
                    'ampel' => $this->getAmpelStatus($utilizationRate),
                    'ampel_message' => $this->getAmpelMessage($utilizationRate),
                    'budget_breakdown' => $budgetBreakdown,
                ];
            });
    }

    public function calculateTotalHoursForUser(User $user, int $year): float
    {
        $timeHours = $user->budgetEntries
            ->where('cost_type', BudgetEntry::COST_TYPE_TIME)
            ->sum('amount');

        $monetaryEntries = $user->budgetEntries
            ->where('cost_type', BudgetEntry::COST_TYPE_MONETARY);

        $hourlyRate = $user->getHourlyRate();
        $convertedHours = $hourlyRate > 0 
            ? $monetaryEntries->sum('amount') / $hourlyRate 
            : 0;

        return (float) ($timeHours + $convertedHours);
    }

    public function getUserBudgetBreakdown(User $user, int $year): array
    {
        $entries = $user->budgetEntries;
        $hourlyRate = $user->getHourlyRate();

        $personalGoals = $entries->where('type', BudgetEntry::TYPE_PERSONAL_GOAL);
        $teamGoals = $entries->where('type', BudgetEntry::TYPE_TEAM_GOAL);
        $internalTraining = $entries->where('type', BudgetEntry::TYPE_INTERNAL_TRAINING);
        $other = $entries->where('type', BudgetEntry::TYPE_OTHER_INTERNAL);

        $userBudget = $user->userBudgets->first();
        $totalAllowance = $userBudget?->total_allowance_monetary ?? 3000;

        $personalGoalsMonetary = $personalGoals
            ->where('cost_type', BudgetEntry::COST_TYPE_MONETARY)
            ->where('is_deductible_from_allowance', true)
            ->sum('amount');

        return [
            'personal_goals' => [
                'label' => 'Persönliche Ziele',
                'monetary_spent' => round($personalGoalsMonetary, 2),
                'monetary_allowance' => round($totalAllowance, 2),
                'monetary_remaining' => round($totalAllowance - $personalGoalsMonetary, 2),
                'time_hours' => round($personalGoals->where('cost_type', BudgetEntry::COST_TYPE_TIME)->sum('amount'), 2),
                'is_deductible' => true,
            ],
            'team_goals' => [
                'label' => 'Teamziele',
                'time_hours' => round($this->sumAsHours($teamGoals, $hourlyRate), 2),
                'is_deductible' => false,
            ],
            'internal_training' => [
                'label' => 'Interne Schulungen',
                'time_hours' => round($this->sumAsHours($internalTraining, $hourlyRate), 2),
                'is_deductible' => false,
            ],
            'other' => [
                'label' => 'Sonstiges',
                'time_hours' => round($this->sumAsHours($other, $hourlyRate), 2),
                'is_deductible' => false,
            ],
        ];
    }

    protected function sumAsHours(Collection $entries, float $hourlyRate): float
    {
        $timeHours = $entries->where('cost_type', BudgetEntry::COST_TYPE_TIME)->sum('amount');
        
        $monetaryAmount = $entries->where('cost_type', BudgetEntry::COST_TYPE_MONETARY)->sum('amount');
        $convertedHours = $hourlyRate > 0 ? $monetaryAmount / $hourlyRate : 0;

        return $timeHours + $convertedHours;
    }

    /**
     * Calculate actual (IST) hours for past months of the current year.
     * Only sums entries with budget_type = 'used' and converts Euro to hours.
     */
    public function calculateActualHoursForPastMonths(User $user, int $year, int $months, float $hourlyRate = 0): float
    {
        if ($months <= 0) {
            return 0;
        }

        if ($hourlyRate <= 0) {
            $hourlyRate = $user->getHourlyRate();
        }

        $totalEuro = $user->budgetEntries()
            ->whereYear('date', $year)
            ->whereMonth('date', '<=', $months)
            ->where('budget_type', BudgetEntry::BUDGET_TYPE_USED)
            ->sum('amount');
        
        return $hourlyRate > 0 ? (float) ($totalEuro / $hourlyRate) : 0;
    }

    /**
     * Calculate planned (SOLL) hours for past months of the current year.
     * Only sums entries with budget_type = 'available' and converts Euro to hours.
     */
    public function calculatePlannedHoursForPastMonths(User $user, int $year, int $months, float $hourlyRate = 0): float
    {
        if ($months <= 0) {
            return 0;
        }

        if ($hourlyRate <= 0) {
            $hourlyRate = $user->getHourlyRate();
        }

        $totalEuro = $user->budgetEntries()
            ->whereYear('date', $year)
            ->whereMonth('date', '<=', $months)
            ->where('budget_type', BudgetEntry::BUDGET_TYPE_AVAILABLE)
            ->sum('amount');
        
        return $hourlyRate > 0 ? (float) ($totalEuro / $hourlyRate) : 0;
    }

    protected function calculateOpportunityCost(Team $team, int $year, float $plannedHours, float $actualHours): float
    {
        if ($actualHours >= $plannedHours) {
            return 0;
        }

        $unusedHours = $plannedHours - $actualHours;

        $avgHourlyRate = $team->users->avg(function ($user) {
            return $user->getHourlyRate();
        }) ?? 100;

        return $unusedHours * $avgHourlyRate;
    }

    public function getAmpelStatus(float $utilizationRate, float $plannedHours = -1): string
    {
        if ($plannedHours == 0) {
            return self::AMPEL_GRAY;
        }
        
        if ($utilizationRate > self::THRESHOLD_OVER_BUDGET) {
            return self::AMPEL_ORANGE;
        }
        
        if ($utilizationRate >= self::THRESHOLD_GREEN) {
            return self::AMPEL_GREEN;
        }
        
        if ($utilizationRate >= self::THRESHOLD_YELLOW) {
            return self::AMPEL_YELLOW;
        }
        
        return self::AMPEL_RED;
    }

    public function getAmpelMessage(float $utilizationRate, float $plannedHours = -1): string
    {
        $status = $this->getAmpelStatus($utilizationRate, $plannedHours);

        return match ($status) {
            self::AMPEL_GRAY => 'Aktuell keine Budgets hinterlegt',
            self::AMPEL_ORANGE => 'Deutlich über Budget',
            self::AMPEL_GREEN => 'Top Stundennutzung',
            self::AMPEL_YELLOW => 'Stunden nutzen - Achtung',
            self::AMPEL_RED => 'Dringend Gespräch führen',
        };
    }

    public function getChartDataForTeams(int $year, ?int $teamId = null): array
    {
        $details = $this->getAllTeamBudgetDetails($year, $teamId);

        return [
            'labels' => $details->pluck('team_name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Budget SOLL',
                    'data' => $details->pluck('totals.monetary_planned')->toArray(),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.7)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Budget IST',
                    'data' => $details->pluck('totals.monetary_actual')->toArray(),
                    'backgroundColor' => 'rgba(16, 185, 129, 0.7)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    public function getAvailableYears(): array
    {
        $years = BudgetEntry::selectRaw('DISTINCT YEAR(date) as year')
            ->whereNotNull('date')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        if (empty($years)) {
            $years = [date('Y')];
        }

        return $years;
    }

    /**
     * Get detailed budget breakdown for a team including categories and SOLL values.
     */
    public function getTeamBudgetDetails(Team $team, int $year): array
    {
        $userIds = $team->users()->pluck('id');
        
        $entries = BudgetEntry::whereIn('user_id', $userIds)
            ->whereYear('date', $year)
            ->get();

        $avgHourlyRate = $team->users->avg(fn($user) => $user->getHourlyRate()) ?? 100;

        $categories = [
            BudgetEntry::TYPE_PERSONAL_GOAL => ['label' => 'Persönliche Ziele', 'time' => 0, 'monetary' => 0],
            BudgetEntry::TYPE_TEAM_GOAL => ['label' => 'Teamziele', 'time' => 0, 'monetary' => 0],
            BudgetEntry::TYPE_INTERNAL_TRAINING => ['label' => 'Interne Schulungen', 'time' => 0, 'monetary' => 0],
            BudgetEntry::TYPE_OTHER_INTERNAL => ['label' => 'Sonstiges', 'time' => 0, 'monetary' => 0],
        ];

        foreach ($entries as $entry) {
            $type = $entry->type;
            if (!isset($categories[$type])) {
                continue;
            }

            if ($entry->cost_type === BudgetEntry::COST_TYPE_TIME) {
                $categories[$type]['time'] += (float) $entry->amount;
            } else {
                $categories[$type]['monetary'] += (float) $entry->amount;
            }
        }

        $globalBudgets = $team->globalBudgets()->where('year', $year)->get();
        
        $plannedByCategory = [];
        foreach ($globalBudgets as $budget) {
            $plannedByCategory[$budget->category] = (float) $budget->amount_planned;
        }

        $totalMonetaryPlanned = array_sum($plannedByCategory);

        $result = [];
        $totalTimeActual = 0;
        $totalMonetaryActual = 0;

        foreach ($categories as $type => $data) {
            $totalTimeActual += $data['time'];
            $totalMonetaryActual += $data['monetary'];

            $result[$type] = [
                'label' => $data['label'],
                'time_actual' => round($data['time'], 1),
                'monetary_actual' => round($data['monetary'], 2),
            ];
        }

        $utilizationRate = $totalMonetaryPlanned > 0 
            ? ($totalMonetaryActual / $totalMonetaryPlanned) * 100 
            : 0;

        return [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'employee_count' => $team->users->count(),
            'categories' => $result,
            'planned_budgets' => $plannedByCategory,
            'totals' => [
                'time_actual' => round($totalTimeActual, 1),
                'monetary_actual' => round($totalMonetaryActual, 2),
                'monetary_planned' => round($totalMonetaryPlanned, 2),
                'utilization' => round($utilizationRate, 1),
            ],
            'ampel' => $this->getAmpelStatus($utilizationRate),
        ];
    }

    /**
     * Get all team budget details for C-Level overview.
     */
    public function getAllTeamBudgetDetails(int $year, ?int $teamId = null): Collection
    {
        $query = Team::with(['users.careerLevel', 'globalBudgets' => fn($q) => $q->where('year', $year)]);
        
        if ($teamId) {
            $query->where('id', $teamId);
        }

        return $query->get()->map(fn(Team $team) => $this->getTeamBudgetDetails($team, $year));
    }

    /**
     * Get subordinates grouped by ampel status for People Manager ToDo view.
     * Uses teamEmployees() which is based on managedTeams() pivot table.
     * For C-Level: teamId = -1 shows only Head-Ofs.
     */
    public function getSubordinatesGroupedByAmpel(User $manager, int $year, ?int $teamId = null): array
    {
        if ($teamId === -1 && $manager->isCLevel()) {
            return $this->getHeadOfReportsGroupedByAmpel($manager, $year);
        }

        $query = $manager->teamEmployees()
            ->with(['careerLevel', 'team', 'budgetEntries' => function ($q) use ($year) {
                $q->whereYear('date', $year);
            }, 'userBudgets' => function ($q) use ($year) {
                $q->where('year', $year);
            }]);

        if ($teamId && $teamId > 0) {
            $query->where('team_id', $teamId);
        }

        $currentMonth = (int) date('n');
        $pastMonths = $currentMonth - 1;

        $subordinates = $query->get()->map(function (User $employee) use ($year, $pastMonths) {
            $hourlyRate = $employee->getHourlyRate();
            
            $plannedHours = $this->calculatePlannedHoursForPastMonths($employee, $year, $pastMonths, $hourlyRate);
            $actualHours = $this->calculateActualHoursForPastMonths($employee, $year, $pastMonths, $hourlyRate);

            $utilizationRate = $plannedHours > 0
                ? ($actualHours / $plannedHours) * 100
                : 0;

            return [
                'user' => $employee,
                'user_id' => $employee->id,
                'user_name' => $employee->name,
                'team_name' => $employee->team?->name ?? 'Kein Team',
                'planned_hours' => round($plannedHours, 1),
                'actual_hours' => round($actualHours, 1),
                'utilization_rate' => round($utilizationRate, 1),
                'ampel' => $this->getAmpelStatus($utilizationRate, $plannedHours),
                'action_text' => $this->getActionText($utilizationRate, $plannedHours),
            ];
        });

        if ($manager->isCLevel() && ! $teamId) {
            $headOfData = $this->getHeadOfReportsGroupedByAmpel($manager, $year);
            $subordinates = $subordinates->concat(
                collect($headOfData['red'])
                    ->concat($headOfData['yellow'])
                    ->concat($headOfData['green'])
                    ->concat($headOfData['orange'])
                    ->concat($headOfData['gray'])
            );
        }

        return [
            'orange' => $subordinates->where('ampel', self::AMPEL_ORANGE)->values(),
            'red' => $subordinates->where('ampel', self::AMPEL_RED)->values(),
            'yellow' => $subordinates->where('ampel', self::AMPEL_YELLOW)->values(),
            'green' => $subordinates->where('ampel', self::AMPEL_GREEN)->values(),
            'gray' => $subordinates->where('ampel', self::AMPEL_GRAY)->values(),
            'counts' => [
                'orange' => $subordinates->where('ampel', self::AMPEL_ORANGE)->count(),
                'red' => $subordinates->where('ampel', self::AMPEL_RED)->count(),
                'yellow' => $subordinates->where('ampel', self::AMPEL_YELLOW)->count(),
                'green' => $subordinates->where('ampel', self::AMPEL_GREEN)->count(),
                'gray' => $subordinates->where('ampel', self::AMPEL_GRAY)->count(),
                'total' => $subordinates->count(),
            ],
        ];
    }

    /**
     * Get teams that have subordinates for a manager.
     * Uses the manager_team pivot table (managedTeams relationship).
     * For C-Level users, includes a virtual "Head-Ofs" team option.
     */
    public function getManagerTeams(User $manager): Collection
    {
        $teams = $manager->managedTeams()->with('users')->orderBy('name')->get();

        if ($manager->isCLevel()) {
            $headOfCount = $manager->headOfReports()->count();
            if ($headOfCount > 0) {
                $virtualTeam = new Team([
                    'id' => -1,
                    'name' => 'Head-Ofs',
                ]);
                $virtualTeam->setAttribute('id', -1);
                $virtualTeam->setRelation('users', collect());

                $teams->prepend($virtualTeam);
            }
        }

        return $teams;
    }

    /**
     * Get Head-Of reports grouped by ampel status (for C-Level dashboard).
     */
    public function getHeadOfReportsGroupedByAmpel(User $cLevelUser, int $year): array
    {
        $currentMonth = (int) date('n');
        $pastMonths = $currentMonth - 1;

        $headOfs = $cLevelUser->headOfReports()
            ->with(['careerLevel', 'team'])
            ->get()
            ->map(function (User $employee) use ($year, $pastMonths) {
                $hourlyRate = $employee->getHourlyRate();
                
                $plannedHours = $this->calculatePlannedHoursForPastMonths($employee, $year, $pastMonths, $hourlyRate);
                $actualHours = $this->calculateActualHoursForPastMonths($employee, $year, $pastMonths, $hourlyRate);

                $utilizationRate = $plannedHours > 0
                    ? ($actualHours / $plannedHours) * 100
                    : 0;

                return [
                    'user' => $employee,
                    'user_id' => $employee->id,
                    'user_name' => $employee->name,
                    'team_name' => $employee->team?->name ?? 'Kein Team',
                    'planned_hours' => round($plannedHours, 1),
                    'actual_hours' => round($actualHours, 1),
                    'utilization_rate' => round($utilizationRate, 1),
                    'ampel' => $this->getAmpelStatus($utilizationRate, $plannedHours),
                    'action_text' => $this->getActionText($utilizationRate, $plannedHours),
                    'is_head_of' => true,
                ];
            });

        return [
            'orange' => $headOfs->where('ampel', self::AMPEL_ORANGE)->values(),
            'red' => $headOfs->where('ampel', self::AMPEL_RED)->values(),
            'yellow' => $headOfs->where('ampel', self::AMPEL_YELLOW)->values(),
            'green' => $headOfs->where('ampel', self::AMPEL_GREEN)->values(),
            'gray' => $headOfs->where('ampel', self::AMPEL_GRAY)->values(),
            'counts' => [
                'orange' => $headOfs->where('ampel', self::AMPEL_ORANGE)->count(),
                'red' => $headOfs->where('ampel', self::AMPEL_RED)->count(),
                'yellow' => $headOfs->where('ampel', self::AMPEL_YELLOW)->count(),
                'green' => $headOfs->where('ampel', self::AMPEL_GREEN)->count(),
                'gray' => $headOfs->where('ampel', self::AMPEL_GRAY)->count(),
                'total' => $headOfs->count(),
            ],
        ];
    }

    /**
     * Get budget overview for Head-Ofs (for C-Level summary card).
     */
    public function getHeadOfsBudgetOverview(User $cLevelUser, int $year): array
    {
        $headOfs = $cLevelUser->headOfReports()->get();
        $employeeCount = $headOfs->count();
        $budget = $employeeCount * 3000;

        $userIds = $headOfs->pluck('id');
        $entries = BudgetEntry::whereIn('user_id', $userIds)
            ->whereYear('date', $year)
            ->where('type', BudgetEntry::TYPE_PERSONAL_GOAL)
            ->where('is_deductible_from_allowance', true)
            ->get();

        $spent = $entries->where('cost_type', BudgetEntry::COST_TYPE_MONETARY)->sum('amount');
        $percentage = $budget > 0 ? round(($spent / $budget) * 100, 0) : 0;

        return [
            'team_id' => -1,
            'team_name' => 'Head-Ofs',
            'employee_count' => $employeeCount,
            'weiterbildung' => [
                'budget' => $budget,
                'spent' => round($spent, 2),
                'remaining' => round($budget - $spent, 2),
                'percentage' => $percentage,
                'ampel' => $this->getAmpelStatus($percentage),
            ],
            'is_virtual' => true,
        ];
    }

    /**
     * Get all teams (for Admin view).
     */
    public function getAllTeams(): Collection
    {
        return Team::with('users')->orderBy('name')->get();
    }

    /**
     * Get budget overview for manager's teams with summaries (like C-Level dashboard).
     * For C-Level users, includes Head-Ofs as a virtual team.
     * If $teamId is provided, filters to only that team (or Head-Ofs if -1).
     */
    public function getManagerTeamsBudgetOverview(User $manager, int $year, ?int $teamId = null): array
    {
        $query = $manager->managedTeams()->with('users')->orderBy('name');
        
        if ($teamId && $teamId > 0) {
            $query->where('teams.id', $teamId);
        }
        
        $teams = $query->get();

        $teamSummaries = $teams->map(function (Team $team) use ($year) {
            $employeeCount = $team->users->count();
            $budget = $employeeCount * 3000;

            $userIds = $team->users->pluck('id');
            
            // Persönliche Ziele (wie im C-Level Dashboard)
            $personalGoalsSpent = BudgetEntry::whereIn('user_id', $userIds)
                ->whereYear('date', $year)
                ->where('type', BudgetEntry::TYPE_PERSONAL_GOAL)
                ->sum('amount');

            // Externe Schulungen (TrainingBookings)
            $trainingCosts = \App\Models\TrainingBooking::whereIn('user_id', $userIds)
                ->whereYear('created_at', $year)
                ->sum('net_cost');

            $spent = $personalGoalsSpent + $trainingCosts;
            $percentage = $budget > 0 ? round(($spent / $budget) * 100, 0) : 0;

            // Service Development Daten
            $teamGoals = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_TEAM_GOAL)
                ->whereYear('date', $year)
                ->sum('amount');

            $internalTraining = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_INTERNAL_TRAINING)
                ->whereYear('date', $year)
                ->sum('amount');

            $other = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_OTHER_INTERNAL)
                ->whereYear('date', $year)
                ->sum('amount');

            $serviceDevSpent = $teamGoals + $internalTraining + $other;
            
            $serviceDevBudget = \App\Models\GlobalBudget::where('team_id', $team->id)
                ->where('year', $year)
                ->where('category', 'Service')
                ->value('amount_planned') ?? 0;
            $serviceDevPercentage = $serviceDevBudget > 0 ? round(($serviceDevSpent / $serviceDevBudget) * 100, 0) : 0;

            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'employee_count' => $employeeCount,
                'weiterbildung' => [
                    'budget' => $budget,
                    'spent' => round($spent, 2),
                    'remaining' => round($budget - $spent, 2),
                    'percentage' => $percentage,
                    'ampel' => $this->getAmpelStatus($percentage),
                ],
                'service_dev' => [
                    'budget' => round($serviceDevBudget, 2),
                    'spent' => round($serviceDevSpent, 2),
                    'team_goals' => round($teamGoals, 2),
                    'internal_training' => round($internalTraining, 2),
                    'other' => round($other, 2),
                    'percentage' => $serviceDevPercentage,
                ],
            ];
        });

        if ($manager->isCLevel()) {
            if ($teamId === -1) {
                $headOfOverview = $this->getHeadOfsBudgetOverview($manager, $year);
                $teamSummaries = collect([$headOfOverview]);
            } elseif (!$teamId) {
                $headOfOverview = $this->getHeadOfsBudgetOverview($manager, $year);
                if ($headOfOverview['employee_count'] > 0) {
                    $teamSummaries->prepend($headOfOverview);
                }
            }
        }

        $totalBudget = $teamSummaries->sum('weiterbildung.budget');
        $totalSpent = $teamSummaries->sum('weiterbildung.spent');
        $totalPercentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 0) : 0;

        // Service Development Summen
        $serviceDevBudget = $teamSummaries->sum('service_dev.budget');
        $serviceDevSpent = $teamSummaries->sum('service_dev.spent');
        $serviceDevPercentage = $serviceDevBudget > 0 ? round(($serviceDevSpent / $serviceDevBudget) * 100, 0) : 0;

        return [
            'teams' => $teamSummaries,
            'summary' => [
                'team_count' => $teamSummaries->count(),
                'employee_count' => $teamSummaries->sum('employee_count'),
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'remaining' => $totalBudget - $totalSpent,
                'percentage' => $totalPercentage,
                'ampel' => $this->getAmpelStatus($totalPercentage),
            ],
            'service_dev_summary' => [
                'planned_budget' => $serviceDevBudget,
                'total_spent' => $serviceDevSpent,
                'team_goals' => $teamSummaries->sum('service_dev.team_goals'),
                'internal_training' => $teamSummaries->sum('service_dev.internal_training'),
                'other' => $teamSummaries->sum('service_dev.other'),
                'remaining' => $serviceDevBudget - $serviceDevSpent,
                'percentage' => $serviceDevPercentage,
            ],
        ];
    }

    /**
     * Get budget overview for ALL teams (Admin view).
     * If $teamId is provided, filters to only that team.
     */
    public function getAllTeamsBudgetOverview(int $year, ?int $teamId = null): array
    {
        if ($teamId && $teamId > 0) {
            $teams = Team::with('users')->where('id', $teamId)->get();
        } else {
            $teams = $this->getAllTeams();
        }
        
        $teamSummaries = $teams->map(function (Team $team) use ($year) {
            $employeeCount = $team->users->count();
            $budget = $employeeCount * 3000;
            
            $userIds = $team->users->pluck('id');
            
            // Persönliche Ziele (wie im C-Level Dashboard)
            $personalGoalsSpent = BudgetEntry::whereIn('user_id', $userIds)
                ->whereYear('date', $year)
                ->where('type', BudgetEntry::TYPE_PERSONAL_GOAL)
                ->sum('amount');

            // Externe Schulungen (TrainingBookings)
            $trainingCosts = \App\Models\TrainingBooking::whereIn('user_id', $userIds)
                ->whereYear('created_at', $year)
                ->sum('net_cost');

            $spent = $personalGoalsSpent + $trainingCosts;
            $percentage = $budget > 0 ? round(($spent / $budget) * 100, 0) : 0;

            // Service Development Daten
            $teamGoals = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_TEAM_GOAL)
                ->whereYear('date', $year)
                ->sum('amount');

            $internalTraining = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_INTERNAL_TRAINING)
                ->whereYear('date', $year)
                ->sum('amount');

            $other = BudgetEntry::whereIn('user_id', $userIds)
                ->where('type', BudgetEntry::TYPE_OTHER_INTERNAL)
                ->whereYear('date', $year)
                ->sum('amount');

            $serviceDevSpent = $teamGoals + $internalTraining + $other;
            
            $serviceDevBudget = \App\Models\GlobalBudget::where('team_id', $team->id)
                ->where('year', $year)
                ->where('category', 'Service')
                ->value('amount_planned') ?? 0;
            $serviceDevPercentage = $serviceDevBudget > 0 ? round(($serviceDevSpent / $serviceDevBudget) * 100, 0) : 0;
            
            return [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'employee_count' => $employeeCount,
                'weiterbildung' => [
                    'budget' => $budget,
                    'spent' => round($spent, 2),
                    'remaining' => round($budget - $spent, 2),
                    'percentage' => $percentage,
                    'ampel' => $this->getAmpelStatus($percentage),
                ],
                'service_dev' => [
                    'budget' => round($serviceDevBudget, 2),
                    'spent' => round($serviceDevSpent, 2),
                    'team_goals' => round($teamGoals, 2),
                    'internal_training' => round($internalTraining, 2),
                    'other' => round($other, 2),
                    'percentage' => $serviceDevPercentage,
                ],
            ];
        });

        $totalBudget = $teamSummaries->sum('weiterbildung.budget');
        $totalSpent = $teamSummaries->sum('weiterbildung.spent');
        $totalPercentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 0) : 0;

        // Service Development Summen
        $serviceDevBudgetTotal = $teamSummaries->sum('service_dev.budget');
        $serviceDevSpentTotal = $teamSummaries->sum('service_dev.spent');
        $serviceDevPercentageTotal = $serviceDevBudgetTotal > 0 ? round(($serviceDevSpentTotal / $serviceDevBudgetTotal) * 100, 0) : 0;

        return [
            'teams' => $teamSummaries,
            'summary' => [
                'team_count' => $teams->count(),
                'employee_count' => $teamSummaries->sum('employee_count'),
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'remaining' => $totalBudget - $totalSpent,
                'percentage' => $totalPercentage,
                'ampel' => $this->getAmpelStatus($totalPercentage),
            ],
            'service_dev_summary' => [
                'planned_budget' => $serviceDevBudgetTotal,
                'total_spent' => $serviceDevSpentTotal,
                'team_goals' => $teamSummaries->sum('service_dev.team_goals'),
                'internal_training' => $teamSummaries->sum('service_dev.internal_training'),
                'other' => $teamSummaries->sum('service_dev.other'),
                'remaining' => $serviceDevBudgetTotal - $serviceDevSpentTotal,
                'percentage' => $serviceDevPercentageTotal,
            ],
        ];
    }

    /**
     * Get ALL subordinates grouped by ampel status (Admin view).
     */
    public function getAllSubordinatesGroupedByAmpel(int $year, ?int $teamId = null): array
    {
        $currentMonth = (int) date('n');
        $pastMonths = $currentMonth - 1;

        $query = User::with(['careerLevel', 'team']);

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        $subordinates = $query->get()->map(function (User $employee) use ($year, $pastMonths) {
            $hourlyRate = $employee->getHourlyRate();
            
            $plannedHours = $this->calculatePlannedHoursForPastMonths($employee, $year, $pastMonths, $hourlyRate);
            $actualHours = $this->calculateActualHoursForPastMonths($employee, $year, $pastMonths, $hourlyRate);

            $utilizationRate = $plannedHours > 0 
                ? ($actualHours / $plannedHours) * 100 
                : 0;

            return [
                'user' => $employee,
                'user_id' => $employee->id,
                'user_name' => $employee->name,
                'team_name' => $employee->team?->name ?? 'Kein Team',
                'planned_hours' => round($plannedHours, 1),
                'actual_hours' => round($actualHours, 1),
                'utilization_rate' => round($utilizationRate, 1),
                'ampel' => $this->getAmpelStatus($utilizationRate, $plannedHours),
                'action_text' => $this->getActionText($utilizationRate, $plannedHours),
            ];
        });

        return [
            'orange' => $subordinates->where('ampel', self::AMPEL_ORANGE)->values(),
            'red' => $subordinates->where('ampel', self::AMPEL_RED)->values(),
            'yellow' => $subordinates->where('ampel', self::AMPEL_YELLOW)->values(),
            'green' => $subordinates->where('ampel', self::AMPEL_GREEN)->values(),
            'gray' => $subordinates->where('ampel', self::AMPEL_GRAY)->values(),
            'counts' => [
                'orange' => $subordinates->where('ampel', self::AMPEL_ORANGE)->count(),
                'red' => $subordinates->where('ampel', self::AMPEL_RED)->count(),
                'yellow' => $subordinates->where('ampel', self::AMPEL_YELLOW)->count(),
                'green' => $subordinates->where('ampel', self::AMPEL_GREEN)->count(),
                'gray' => $subordinates->where('ampel', self::AMPEL_GRAY)->count(),
                'total' => $subordinates->count(),
            ],
        ];
    }

    /**
     * Get action text based on utilization rate.
     */
    protected function getActionText(float $utilizationRate, float $plannedHours = -1): string
    {
        $status = $this->getAmpelStatus($utilizationRate, $plannedHours);

        return match ($status) {
            self::AMPEL_GRAY => 'Kein Budget',
            self::AMPEL_ORANGE => 'Bitte einchecken',
            self::AMPEL_GREEN => 'Top Stundennutzung',
            self::AMPEL_YELLOW => 'Stunden nutzen',
            self::AMPEL_RED => 'Eskalation',
        };
    }

    /**
     * Get all employees of a team with their ampel status.
     */
    public function getTeamEmployeesWithAmpel(int $teamId, int $year): Collection
    {
        return User::where('team_id', $teamId)
            ->with(['careerLevel', 'budgetEntries' => function ($q) use ($year) {
                $q->whereYear('date', $year);
            }, 'userBudgets' => function ($q) use ($year) {
                $q->where('year', $year);
            }])
            ->orderBy('name')
            ->get()
            ->map(function (User $employee) use ($year) {
                $targetHours = $employee->target_hours_per_year ?? 0;
                $actualHours = $this->calculateTotalHoursForUser($employee, $year);
                $utilizationRate = $targetHours > 0 
                    ? ($actualHours / $targetHours) * 100 
                    : 0;

                $budgetBreakdown = $this->getUserBudgetBreakdown($employee, $year);
                $monetarySpent = $budgetBreakdown['personal_goals']['monetary_spent'] ?? 0;
                $monetaryAllowance = $budgetBreakdown['personal_goals']['monetary_allowance'] ?? 3000;

                return [
                    'user' => $employee,
                    'user_id' => $employee->id,
                    'user_name' => $employee->name,
                    'target_hours' => round($targetHours, 1),
                    'actual_hours' => round($actualHours, 1),
                    'utilization_rate' => round($utilizationRate, 1),
                    'monetary_spent' => round($monetarySpent, 2),
                    'monetary_allowance' => round($monetaryAllowance, 2),
                    'monetary_remaining' => round($monetaryAllowance - $monetarySpent, 2),
                    'ampel' => $this->getAmpelStatus($utilizationRate),
                    'ampel_message' => $this->getAmpelMessage($utilizationRate),
                ];
            });
    }
}
