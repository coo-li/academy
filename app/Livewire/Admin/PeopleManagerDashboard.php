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

    protected BudgetDashboardService $dashboardService;

    public function boot(BudgetDashboardService $dashboardService): void
    {
        $this->dashboardService = $dashboardService;
    }

    public function mount(): void
    {
        $this->availableYears = $this->dashboardService->getAvailableYears();
        $this->selectedYear = $this->availableYears[0] ?? date('Y');
    }

    public function getTeamsProperty()
    {
        return $this->dashboardService->getManagerTeams(Auth::user());
    }

    public function getGroupedSubordinatesProperty()
    {
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
        return view('livewire.admin.people-manager-dashboard', [
            'teams' => $this->teams,
            'grouped' => $this->groupedSubordinates,
            'subordinates' => $this->subordinates,
        ])->layout('layouts.app');
    }
}
