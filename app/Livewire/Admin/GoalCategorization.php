<?php

namespace App\Livewire\Admin;

use App\Models\BudgetEntry;
use App\Models\Team;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class GoalCategorization extends Component
{
    use WithPagination;

    public int $selectedYear;
    public ?int $selectedTeamId = null;
    public ?string $selectedType = null;
    public ?string $categoryFilter = null;
    public array $availableYears = [];
    public bool $showAllTeams = false;

    protected $listeners = ['refreshGoals' => '$refresh'];

    protected $queryString = ['scope'];
    public ?string $scope = null;

    public function mount(): void
    {
        $currentYear = (int) date('Y');
        $this->availableYears = range($currentYear - 1, $currentYear + 1);
        $this->selectedYear = $currentYear;

        $this->showAllTeams = $this->scope === 'all' && Auth::user()->isAdmin();
    }

    public function getTeamsProperty()
    {
        $user = Auth::user();

        if ($this->showAllTeams) {
            return Team::orderBy('name')->get();
        }

        return $user->managedTeams()->orderBy('name')->get();
    }

    public function getGoalsProperty()
    {
        $user = Auth::user();

        $query = BudgetEntry::with(['user.team', 'user.careerLevel'])
            ->active()
            ->whereYear('date', $this->selectedYear);

        if (!$this->showAllTeams) {
            $teamIds = $user->managedTeams()->pluck('teams.id');
            $query->whereHas('user', function ($q) use ($teamIds) {
                $q->whereIn('team_id', $teamIds);
            });
        }

        if ($this->selectedTeamId) {
            $query->whereHas('user', function ($q) {
                $q->where('team_id', $this->selectedTeamId);
            });
        }

        if ($this->selectedType) {
            $query->where('type', $this->selectedType);
        }

        if ($this->categoryFilter === 'uncategorized') {
            $query->uncategorized();
        } elseif ($this->categoryFilter === 'none') {
            $query->withCategory('none');
        } elseif ($this->categoryFilter && in_array($this->categoryFilter, ['A', 'B', 'C'])) {
            $query->withCategory($this->categoryFilter);
        }

        return $query->orderBy('date', 'desc')->paginate(50);
    }

    public function getStatsProperty(): array
    {
        $user = Auth::user();

        $baseQuery = BudgetEntry::active()->whereYear('date', $this->selectedYear);

        if (!$this->showAllTeams) {
            $teamIds = $user->managedTeams()->pluck('teams.id');
            $baseQuery->whereHas('user', function ($q) use ($teamIds) {
                $q->whereIn('team_id', $teamIds);
            });
        }

        if ($this->selectedTeamId) {
            $baseQuery->whereHas('user', function ($q) {
                $q->where('team_id', $this->selectedTeamId);
            });
        }

        $total = (clone $baseQuery)->count();
        $categorized = (clone $baseQuery)->categorized()->count();
        $uncategorized = (clone $baseQuery)->uncategorized()->count();
        $categoryA = (clone $baseQuery)->withCategory('A')->count();
        $categoryB = (clone $baseQuery)->withCategory('B')->count();
        $categoryC = (clone $baseQuery)->withCategory('C')->count();
        $categoryNone = (clone $baseQuery)->withCategory('none')->count();

        return [
            'total' => $total,
            'categorized' => $categorized,
            'uncategorized' => $uncategorized,
            'category_a' => $categoryA,
            'category_b' => $categoryB,
            'category_c' => $categoryC,
            'category_none' => $categoryNone,
            'progress' => $total > 0 ? round(($categorized / $total) * 100) : 0,
        ];
    }

    public function updateCategory(int $entryId, ?string $category): void
    {
        $entry = BudgetEntry::findOrFail($entryId);

        $user = Auth::user();
        if (!$this->showAllTeams) {
            $teamIds = $user->managedTeams()->pluck('teams.id');
            if (!$teamIds->contains($entry->user->team_id)) {
                return;
            }
        }

        $entry->update([
            'goal_category' => $category ?: null,
        ]);
    }

    public function updatedSelectedYear(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedTeamId(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedType(): void
    {
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function convertToHours(BudgetEntry $entry): float
    {
        if ($entry->cost_type === BudgetEntry::COST_TYPE_TIME) {
            return (float) $entry->amount;
        }

        $hourlyRate = $entry->user?->getHourlyRate() ?? 100;
        return $hourlyRate > 0 ? round((float) $entry->amount / $hourlyRate, 1) : 0;
    }

    public function render()
    {
        return view('livewire.admin.goal-categorization', [
            'teams' => $this->teams,
            'goals' => $this->goals,
            'stats' => $this->stats,
            'goalCategories' => BudgetEntry::GOAL_CATEGORIES,
            'typeLabels' => [
                BudgetEntry::TYPE_PERSONAL_GOAL => 'Persönliche Ziele',
                BudgetEntry::TYPE_TEAM_GOAL => 'Teamziele',
                BudgetEntry::TYPE_INTERNAL_TRAINING => 'Interne Schulungen',
                BudgetEntry::TYPE_OTHER_INTERNAL => 'Sonstiges',
            ],
            'showAllTeams' => $this->showAllTeams,
        ])->layout('layouts.app');
    }
}
