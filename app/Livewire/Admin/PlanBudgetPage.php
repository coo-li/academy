<?php

namespace App\Livewire\Admin;

use App\Models\CompanyBudgetSetting;
use App\Models\GlobalBudget;
use App\Models\Team;
use App\Models\TeamBudgetAllocation;
use Livewire\Component;

class PlanBudgetPage extends Component
{
    public int $selectedYear;
    public array $availableYears = [];

    // Unternehmenseinstellungen
    public float $revenueTarget = 0;
    public float $serviceDevPercentage = 0;

    // Team-Allokationen (team_id => percentage)
    public array $teamAllocations = [];

    public function mount(): void
    {
        $currentYear = (int) date('Y');
        $this->availableYears = range($currentYear, $currentYear + 3);
        $this->selectedYear = $currentYear;
        
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $setting = CompanyBudgetSetting::forYear($this->selectedYear);
        $this->revenueTarget = (float) ($setting?->revenue_target ?? 0);
        $this->serviceDevPercentage = (float) ($setting?->service_dev_percentage ?? 0);

        $this->teamAllocations = [];
        $teams = Team::orderBy('name')->get();
        
        foreach ($teams as $team) {
            $allocation = TeamBudgetAllocation::where('year', $this->selectedYear)
                ->where('team_id', $team->id)
                ->first();
            
            $this->teamAllocations[$team->id] = [
                'team_name' => $team->name,
                'percentage' => (float) ($allocation?->allocation_percentage ?? 0),
            ];
        }
    }

    public function updatedSelectedYear(): void
    {
        $this->loadSettings();
    }

    public function getServiceDevBudgetProperty(): float
    {
        $revenue = is_numeric($this->revenueTarget) ? (float) $this->revenueTarget : 0;
        $percentage = is_numeric($this->serviceDevPercentage) ? (float) $this->serviceDevPercentage : 0;
        return $revenue * ($percentage / 100);
    }

    public function getTotalAllocationPercentageProperty(): float
    {
        return collect($this->teamAllocations)->sum(fn($item) => (float) ($item['percentage'] ?? 0));
    }

    public function getTeamBudgetsProperty(): array
    {
        $serviceDevBudget = $this->serviceDevBudget;
        $budgets = [];

        foreach ($this->teamAllocations as $teamId => $data) {
            $percentage = (float) ($data['percentage'] ?? 0);
            $budgets[$teamId] = [
                'team_name' => $data['team_name'],
                'percentage' => $percentage,
                'amount' => $serviceDevBudget * ($percentage / 100),
            ];
        }

        return $budgets;
    }

    public function save(): void
    {
        $this->validate([
            'revenueTarget' => 'required|numeric|min:0',
            'serviceDevPercentage' => 'required|numeric|min:0|max:100',
            'teamAllocations.*.percentage' => 'required|numeric|min:0|max:100',
        ]);

        // Unternehmenseinstellungen speichern
        CompanyBudgetSetting::updateOrCreate(
            ['year' => $this->selectedYear],
            [
                'revenue_target' => $this->revenueTarget,
                'service_dev_percentage' => $this->serviceDevPercentage,
            ]
        );

        // Team-Allokationen speichern
        foreach ($this->teamAllocations as $teamId => $data) {
            $percentage = (float) ($data['percentage'] ?? 0);
            
            TeamBudgetAllocation::updateOrCreate(
                [
                    'year' => $this->selectedYear,
                    'team_id' => $teamId,
                ],
                [
                    'allocation_percentage' => $percentage,
                ]
            );

            // Auch GlobalBudget aktualisieren für Kompatibilität mit bestehendem Dashboard
            $allocatedAmount = $this->serviceDevBudget * ($percentage / 100);
            GlobalBudget::updateOrCreate(
                [
                    'year' => $this->selectedYear,
                    'team_id' => $teamId,
                    'category' => 'Service',
                ],
                [
                    'budget_type' => 'Echtkosten',
                    'amount_planned' => $allocatedAmount,
                ]
            );
        }

        session()->flash('success', 'Planbudgets für ' . $this->selectedYear . ' wurden gespeichert.');
    }

    public function distributeEvenly(): void
    {
        $teamCount = count($this->teamAllocations);
        if ($teamCount === 0) {
            return;
        }

        $percentagePerTeam = round(100 / $teamCount, 2);
        
        foreach ($this->teamAllocations as $teamId => $data) {
            $this->teamAllocations[$teamId]['percentage'] = $percentagePerTeam;
        }
    }

    public function render()
    {
        return view('livewire.admin.plan-budget-page', [
            'serviceDevBudget' => $this->serviceDevBudget,
            'totalAllocationPercentage' => $this->totalAllocationPercentage,
            'teamBudgets' => $this->teamBudgets,
        ])->layout('layouts.app');
    }
}
