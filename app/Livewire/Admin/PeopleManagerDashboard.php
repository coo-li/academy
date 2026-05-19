<?php

namespace App\Livewire\Admin;

use App\Services\BudgetDashboardService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PeopleManagerDashboard extends Component
{
    public int $selectedYear;
    public ?int $selectedTeamId = null;
    public array $availableYears = [];
    public bool $showAllTeams = false;
    public ?string $filterAmpel = null;

    protected BudgetDashboardService $dashboardService;

    public function boot(BudgetDashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    public function mount(): void
    {
        $this->availableYears = $this->dashboardService->getAvailableYears();
        $this->selectedYear = $this->availableYears[0] ?? date('Y');
        
        $scope = request()->query('scope');
        $this->showAllTeams = $scope === 'all' && Auth::user()->isAdmin();
    }

    public function getTeamsProperty()
    {
        if ($this->showAllTeams) {
            return $this->dashboardService->getAllTeams();
        }
        return $this->dashboardService->getManagerTeams(Auth::user());
    }

    public function getTeamsBudgetOverviewProperty()
    {
        if ($this->showAllTeams) {
            return $this->dashboardService->getAllTeamsBudgetOverview(
                $this->selectedYear,
                $this->selectedTeamId
            );
        }
        return $this->dashboardService->getManagerTeamsBudgetOverview(
            Auth::user(),
            $this->selectedYear,
            $this->selectedTeamId
        );
    }

    public function getGroupedSubordinatesProperty()
    {
        if ($this->showAllTeams) {
            return $this->dashboardService->getAllSubordinatesGroupedByAmpel(
                $this->selectedYear,
                $this->selectedTeamId
            );
        }
        return $this->dashboardService->getSubordinatesGroupedByAmpel(
            Auth::user(),
            $this->selectedYear,
            $this->selectedTeamId
        );
    }

    public function getSubordinatesProperty()
    {
        return $this->dashboardService->getSubordinateBudgetOverview(
            Auth::user(),
            $this->selectedYear
        );
    }

    public function render()
    {
        $budgetOverview = $this->teamsBudgetOverview;

        return view('livewire.admin.people-manager-dashboard', [
            'teams' => $this->teams,
            'teamSummaries' => $budgetOverview['teams'],
            'budgetSummary' => $budgetOverview['summary'],
            'budgetOverview' => $budgetOverview,
            'grouped' => $this->groupedSubordinates,
            'subordinates' => $this->subordinates,
            'showAllTeams' => $this->showAllTeams,
            'isCLevel' => Auth::user()->isCLevel(),
        ])->layout('layouts.app');
    }
}
