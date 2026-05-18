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

    public const THRESHOLD_GREEN = 90;
    public const THRESHOLD_YELLOW = 75;

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

    public function getAmpelStatus(float $utilizationRate): string
    {
        if ($utilizationRate >= self::THRESHOLD_GREEN) {
            return self::AMPEL_GREEN;
        }
        
        if ($utilizationRate >= self::THRESHOLD_YELLOW) {
            return self::AMPEL_YELLOW;
        }
        
        return self::AMPEL_RED;
    }

    public function getAmpelMessage(float $utilizationRate): string
    {
        $status = $this->getAmpelStatus($utilizationRate);

        return match ($status) {
            self::AMPEL_GREEN => 'Top Auslastung',
            self::AMPEL_YELLOW => 'Mitarbeiter für Ziel-Check kontaktieren',
            self::AMPEL_RED => 'Fokus-Intervention nötig',
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
     */
    public function getSubordinatesGroupedByAmpel(User $manager, int $year, ?int $teamId = null): array
    {
        $query = $manager->subordinates()
            ->with(['careerLevel', 'team', 'budgetEntries' => function ($q) use ($year) {
                $q->whereYear('date', $year);
            }, 'userBudgets' => function ($q) use ($year) {
                $q->where('year', $year);
            }]);

        if ($teamId) {
            $query->where('team_id', $teamId);
        }

        $subordinates = $query->get()->map(function (User $employee) use ($year) {
            $targetHours = $employee->target_hours_per_year ?? 0;
            $actualHours = $this->calculateTotalHoursForUser($employee, $year);
            $utilizationRate = $targetHours > 0 
                ? ($actualHours / $targetHours) * 100 
                : 0;

            return [
                'user' => $employee,
                'user_id' => $employee->id,
                'user_name' => $employee->name,
                'team_name' => $employee->team?->name ?? 'Kein Team',
                'target_hours' => round($targetHours, 1),
                'actual_hours' => round($actualHours, 1),
                'utilization_rate' => round($utilizationRate, 1),
                'ampel' => $this->getAmpelStatus($utilizationRate),
                'action_text' => $this->getActionText($utilizationRate),
            ];
        });

        return [
            'red' => $subordinates->where('ampel', self::AMPEL_RED)->values(),
            'yellow' => $subordinates->where('ampel', self::AMPEL_YELLOW)->values(),
            'green' => $subordinates->where('ampel', self::AMPEL_GREEN)->values(),
            'counts' => [
                'red' => $subordinates->where('ampel', self::AMPEL_RED)->count(),
                'yellow' => $subordinates->where('ampel', self::AMPEL_YELLOW)->count(),
                'green' => $subordinates->where('ampel', self::AMPEL_GREEN)->count(),
                'total' => $subordinates->count(),
            ],
        ];
    }

    /**
     * Get teams that have subordinates for a manager.
     */
    public function getManagerTeams(User $manager): Collection
    {
        return Team::with('users')
            ->whereHas('users', function ($query) use ($manager) {
                $query->where('head_of_user_id', $manager->id);
            })->orderBy('name')->get();
    }

    /**
     * Get action text based on utilization rate.
     */
    protected function getActionText(float $utilizationRate): string
    {
        $status = $this->getAmpelStatus($utilizationRate);

        return match ($status) {
            self::AMPEL_GREEN => 'Alles im grünen Bereich',
            self::AMPEL_YELLOW => 'Sollte sich für Ziel-Check melden',
            self::AMPEL_RED => 'Fokus-Gespräch führen',
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
