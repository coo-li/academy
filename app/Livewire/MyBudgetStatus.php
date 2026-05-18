<?php

namespace App\Livewire;

use App\Models\BudgetEntry;
use App\Models\TrainingBooking;
use App\Models\User;
use App\Services\BudgetDashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MyBudgetStatus extends Component
{
    public int $selectedYear;
    public string $selectedPeriod = 'year'; // 'year', 'q1', 'q2', 'q3', 'q4', '1'-'12'
    public string $activeTab = 'uebersicht'; // Default: Jahresübersicht mit Matrix
    public array $availableYears = [];
    public ?int $userId = null;
    public bool $viewingOther = false;

    protected BudgetDashboardService $dashboardService;

    public function boot(BudgetDashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    public function mount(?int $userId = null): void
    {
        $this->availableYears = $this->dashboardService->getAvailableYears();
        $this->selectedYear = $this->availableYears[0] ?? (int) date('Y');
        
        if ($userId && $userId !== Auth::id()) {
            if (Auth::user()->hasPeopleManagerAccess()) {
                $this->userId = $userId;
                $this->viewingOther = true;
            }
        }
    }

    public function setPeriod(string $period): void
    {
        $this->selectedPeriod = $period;
    }

    protected function getTargetUser(): User
    {
        if ($this->userId && $this->viewingOther) {
            return User::findOrFail($this->userId);
        }
        return Auth::user();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    protected function applyDateFilter($query)
    {
        $query->whereYear('date', $this->selectedYear);
        
        $months = $this->getSelectedMonths();
        if ($months !== null) {
            $query->whereIn(\DB::raw('MONTH(date)'), $months);
        }
        
        return $query;
    }

    protected function getSelectedMonths(): ?array
    {
        return match ($this->selectedPeriod) {
            'year' => null,
            'q1' => [1, 2, 3],
            'q2' => [4, 5, 6],
            'q3' => [7, 8, 9],
            'q4' => [10, 11, 12],
            default => is_numeric($this->selectedPeriod) ? [(int) $this->selectedPeriod] : null,
        };
    }

    protected function getPeriodDivisor(): int
    {
        return match ($this->selectedPeriod) {
            'year' => 1,
            'q1', 'q2', 'q3', 'q4' => 4,
            default => 12,
        };
    }

    public function getWeiterbildungDataProperty(): array
    {
        $user = $this->getTargetUser();
        $hourlyRate = $user->getHourlyRate();
        
        $userBudget = $user->getBudgetForYear($this->selectedYear);
        $totalAllowance = $userBudget?->total_allowance_monetary ?? 3000;
        
        // Bei Zeitraum-Filter: anteiliges Budget
        $divisor = $this->getPeriodDivisor();
        $periodAllowance = $totalAllowance / $divisor;

        // Persönliche Ziele: IST (verbraucht)
        $queryIst = $user->budgetEntries()
            ->active()
            ->used()
            ->where('type', BudgetEntry::TYPE_PERSONAL_GOAL);
        $this->applyDateFilter($queryIst);
        $istEuros = $queryIst->sum('amount');

        // Persönliche Ziele: SOLL (geplant) - DAS geht vom Budget ab!
        $querySoll = $user->budgetEntries()
            ->active()
            ->available()
            ->where('type', BudgetEntry::TYPE_PERSONAL_GOAL);
        $this->applyDateFilter($querySoll);
        $sollEuros = $querySoll->sum('amount');

        // Externe Weiterbildungen (TrainingBookings) - echte Kosten!
        $trainingBookings = $this->getTrainingBookingsForPeriod($user);
        $trainingCostsEuros = $trainingBookings->sum('net_cost');
        $trainingHours = $trainingBookings->where('during_work_hours', true)->sum('hours') ?? 0;

        // Gesamtabzug: Geplante Ziele + Externe Weiterbildungen
        $geplantAbzug = $sollEuros + $trainingCostsEuros;
        $remaining = $periodAllowance - $geplantAbzug;

        // Für Anzeige: in Stunden umrechnen
        $sollHours = $hourlyRate > 0 ? $sollEuros / $hourlyRate : 0;
        $istHours = $hourlyRate > 0 ? $istEuros / $hourlyRate : 0;

        return [
            'total_allowance' => $periodAllowance,
            'full_year_allowance' => $totalAllowance,
            'geplant_euros' => $sollEuros,
            'geplant_hours' => $sollHours,
            'genutzt_euros' => $istEuros,
            'genutzt_hours' => $istHours,
            'remaining' => $remaining,
            'percentage' => $periodAllowance > 0 ? ($geplantAbzug / $periodAllowance) * 100 : 0,
            'hourly_rate' => $hourlyRate,
            'training_costs_euros' => $trainingCostsEuros,
            'training_hours' => $trainingHours,
            'training_count' => $trainingBookings->count(),
        ];
    }

    protected function getTrainingBookingsForPeriod(User $user)
    {
        $query = TrainingBooking::where('user_id', $user->id)
            ->whereYear('created_at', $this->selectedYear);

        $months = $this->getSelectedMonths();
        if ($months !== null) {
            $query->whereIn(\DB::raw('MONTH(created_at)'), $months);
        }

        return $query->get();
    }

    public function getTrainingBookingsProperty()
    {
        $user = $this->getTargetUser();
        return $this->getTrainingBookingsForPeriod($user);
    }

    public function getEntriesByTypeProperty(): array
    {
        $user = $this->getTargetUser();
        $hourlyRate = $user->getHourlyRate();
        
        $getGroupedEntries = function($type) use ($user, $hourlyRate) {
            // Alle Entries (Ist UND Soll) holen
            $query = $user->budgetEntries()
                ->active()
                ->where('type', $type);
            
            $this->applyDateFilter($query);
            $allEntries = $query->get();
            
            // Nach budget_name gruppieren
            $grouped = $allEntries->groupBy('budget_name');
            
            $result = [];
            foreach ($grouped as $budgetName => $entries) {
                $istEntries = $entries->where('budget_type', BudgetEntry::BUDGET_TYPE_USED);
                $sollEntries = $entries->where('budget_type', BudgetEntry::BUDGET_TYPE_AVAILABLE);
                
                $istEuros = $istEntries->sum('amount');
                $sollEuros = $sollEntries->sum('amount');
                
                $istHours = $hourlyRate > 0 ? $istEuros / $hourlyRate : 0;
                $sollHours = $hourlyRate > 0 ? $sollEuros / $hourlyRate : 0;
                
                $verwendung = $sollHours > 0 ? ($istHours / $sollHours) * 100 : 0;
                
                $result[] = [
                    'budget_name' => $budgetName ?: 'Unbenannt',
                    'ist_euros' => $istEuros,
                    'soll_euros' => $sollEuros,
                    'ist_hours' => $istHours,
                    'soll_hours' => $sollHours,
                    'verwendung' => $verwendung,
                    'entries' => $entries,
                ];
            }
            
            return collect($result)->sortByDesc('ist_euros')->values();
        };

        return [
            'personal_goals' => $getGroupedEntries(BudgetEntry::TYPE_PERSONAL_GOAL),
            'team_goals' => $getGroupedEntries(BudgetEntry::TYPE_TEAM_GOAL),
            'internal_training' => $getGroupedEntries(BudgetEntry::TYPE_INTERNAL_TRAINING),
            'other' => $getGroupedEntries(BudgetEntry::TYPE_OTHER_INTERNAL),
        ];
    }

    public function getArchivedEntriesProperty()
    {
        $query = $this->getTargetUser()
            ->budgetEntries()
            ->archived();
        
        $this->applyDateFilter($query);
        
        return $query->orderBy('archived_at', 'desc')->get();
    }

    public function getArchivedCountProperty(): int
    {
        return $this->archivedEntries->count();
    }

    public function getCategoryStatsProperty(): array
    {
        $entries = $this->entriesByType;
        
        $getStats = function($items) {
            $istHours = $items->sum('ist_hours');
            $sollHours = $items->sum('soll_hours');
            return [
                'count' => $items->count(),
                'ist_hours' => $istHours,
                'soll_hours' => $sollHours,
                'verwendung' => $sollHours > 0 ? ($istHours / $sollHours) * 100 : 0,
            ];
        };
        
        return [
            'personal_goals' => $getStats($entries['personal_goals']),
            'team_goals' => $getStats($entries['team_goals']),
            'internal_training' => $getStats($entries['internal_training']),
            'other' => $getStats($entries['other']),
        ];
    }

    public function getTargetUserNameProperty(): string
    {
        return $this->getTargetUser()->name;
    }

    public function getCanDeleteArchivedProperty(): bool
    {
        return Auth::user()->hasAdminAccess();
    }

    public function deleteArchivedEntry(int $entryId): void
    {
        if (!$this->canDeleteArchived) {
            return;
        }

        $entry = BudgetEntry::find($entryId);
        if ($entry && $entry->isArchived()) {
            $entry->delete();
        }
    }

    public function getHourlyRateProperty(): float
    {
        return $this->getTargetUser()->getHourlyRate();
    }

    public function getMonthsProperty(): array
    {
        return [
            '1' => 'Jan',
            '2' => 'Feb', 
            '3' => 'Mär',
            '4' => 'Apr',
            '5' => 'Mai',
            '6' => 'Jun',
            '7' => 'Jul',
            '8' => 'Aug',
            '9' => 'Sep',
            '10' => 'Okt',
            '11' => 'Nov',
            '12' => 'Dez',
        ];
    }

    public function getSelectedPeriodNameProperty(): string
    {
        return match ($this->selectedPeriod) {
            'year' => 'Ganzes Jahr',
            'q1' => 'Q1 (Jan-Mär)',
            'q2' => 'Q2 (Apr-Jun)',
            'q3' => 'Q3 (Jul-Sep)',
            'q4' => 'Q4 (Okt-Dez)',
            default => $this->months[$this->selectedPeriod] ?? $this->selectedPeriod,
        };
    }

    public function getMonthlyBreakdownProperty(): array
    {
        $user = $this->getTargetUser();
        $hourlyRate = $user->getHourlyRate();
        
        $getBreakdownForType = function($type) use ($user, $hourlyRate) {
            // Alle Entries für das Jahr holen (unabhängig vom Periodenfilter)
            $allEntries = $user->budgetEntries()
                ->active()
                ->where('type', $type)
                ->whereYear('date', $this->selectedYear)
                ->get();
            
            // Nach budget_name gruppieren
            $grouped = $allEntries->groupBy('budget_name');
            
            $goals = [];
            $monthTotals = array_fill(1, 12, ['ist' => 0, 'soll' => 0]);
            
            foreach ($grouped as $budgetName => $entries) {
                $goal = [
                    'name' => $budgetName ?: 'Unbenannt',
                    'months' => [],
                    'total_ist' => 0,
                    'total_soll' => 0,
                ];
                
                // Nach Monat gruppieren
                for ($month = 1; $month <= 12; $month++) {
                    $monthEntries = $entries->filter(fn($e) => $e->date->month === $month);
                    
                    $istEuros = $monthEntries->where('budget_type', BudgetEntry::BUDGET_TYPE_USED)->sum('amount');
                    $sollEuros = $monthEntries->where('budget_type', BudgetEntry::BUDGET_TYPE_AVAILABLE)->sum('amount');
                    
                    $istHours = $hourlyRate > 0 ? $istEuros / $hourlyRate : 0;
                    $sollHours = $hourlyRate > 0 ? $sollEuros / $hourlyRate : 0;
                    
                    $goal['months'][$month] = [
                        'ist' => round($istHours, 1),
                        'soll' => round($sollHours, 1),
                    ];
                    
                    $goal['total_ist'] += $istHours;
                    $goal['total_soll'] += $sollHours;
                    
                    $monthTotals[$month]['ist'] += $istHours;
                    $monthTotals[$month]['soll'] += $sollHours;
                }
                
                $goal['total_ist'] = round($goal['total_ist'], 1);
                $goal['total_soll'] = round($goal['total_soll'], 1);
                $goal['verwendung'] = $goal['total_soll'] > 0 
                    ? round(($goal['total_ist'] / $goal['total_soll']) * 100, 0) 
                    : 0;
                
                $goals[] = $goal;
            }
            
            // Nach total_ist sortieren (höchste zuerst)
            usort($goals, fn($a, $b) => $b['total_ist'] <=> $a['total_ist']);
            
            // Monatstotals runden
            foreach ($monthTotals as $month => $totals) {
                $monthTotals[$month]['ist'] = round($totals['ist'], 1);
                $monthTotals[$month]['soll'] = round($totals['soll'], 1);
            }
            
            $totalIst = array_sum(array_column($monthTotals, 'ist'));
            $totalSoll = array_sum(array_column($monthTotals, 'soll'));
            
            return [
                'goals' => $goals,
                'month_totals' => $monthTotals,
                'total_ist' => round($totalIst, 1),
                'total_soll' => round($totalSoll, 1),
                'verwendung' => $totalSoll > 0 ? round(($totalIst / $totalSoll) * 100, 0) : 0,
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
        return view('livewire.my-budget-status', [
            'weiterbildungData' => $this->weiterbildungData,
            'entriesByType' => $this->entriesByType,
            'archivedEntries' => $this->archivedEntries,
            'archivedCount' => $this->archivedCount,
            'categoryStats' => $this->categoryStats,
            'targetUserName' => $this->targetUserName,
            'viewingOther' => $this->viewingOther,
            'canDeleteArchived' => $this->canDeleteArchived,
            'hourlyRate' => $this->hourlyRate,
            'months' => $this->months,
            'selectedPeriodName' => $this->selectedPeriodName,
            'monthlyBreakdown' => $this->monthlyBreakdown,
            'trainingBookings' => $this->trainingBookings,
        ])->layout('layouts.app');
    }
}
