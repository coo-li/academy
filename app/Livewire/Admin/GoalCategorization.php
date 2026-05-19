<?php

namespace App\Livewire\Admin;

use App\Models\BudgetEntry;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class GoalCategorization extends Component
{
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

    /**
     * Gruppierte Ziele: Pro user_id + budget_name nur eine Zeile
     */
    public function getGoalsProperty(): Collection
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

        $entries = $query->orderBy('user_id')->orderBy('budget_name')->get();

        // Nach user_id + budget_name gruppieren
        $grouped = $entries->groupBy(fn($entry) => $entry->user_id . '|' . $entry->budget_name);

        $goals = $grouped->map(function ($group) {
            $first = $group->first();
            $totalHours = $group->sum(fn($entry) => $this->convertEntryToHours($entry));
            
            // Prüfe ob alle Einträge die gleiche Kategorie haben
            $categories = $group->pluck('goal_category')->unique();
            $category = $categories->count() === 1 ? $categories->first() : $first->goal_category;
            
            return (object) [
                'key' => $first->user_id . '|' . $first->budget_name,
                'user_id' => $first->user_id,
                'user' => $first->user,
                'budget_name' => $first->budget_name,
                'label' => $first->label,
                'type' => $first->type,
                'goal_category' => $category,
                'total_hours' => round($totalHours, 1),
                'entry_count' => $group->count(),
                'entry_ids' => $group->pluck('id')->toArray(),
            ];
        })->values();

        // Filter nach Kategorie anwenden
        if ($this->categoryFilter === 'uncategorized') {
            $goals = $goals->filter(fn($g) => $g->goal_category === null);
        } elseif ($this->categoryFilter === 'none') {
            $goals = $goals->filter(fn($g) => $g->goal_category === 'none');
        } elseif ($this->categoryFilter && in_array($this->categoryFilter, ['A', 'B', 'C'])) {
            $goals = $goals->filter(fn($g) => $g->goal_category === $this->categoryFilter);
        }

        return $goals->sortBy(fn($g) => ($g->user->name ?? '') . '|' . $g->budget_name)->values();
    }

    /**
     * Hilfsmethode: Einzelnen Entry in Stunden umrechnen
     */
    protected function convertEntryToHours(BudgetEntry $entry): float
    {
        if ($entry->cost_type === BudgetEntry::COST_TYPE_TIME) {
            return (float) $entry->amount;
        }

        $hourlyRate = $entry->user?->getHourlyRate() ?? 100;
        return $hourlyRate > 0 ? (float) $entry->amount / $hourlyRate : 0;
    }

    /**
     * Stats basierend auf unique Zielen (nicht auf einzelnen Einträgen)
     */
    public function getStatsProperty(): array
    {
        $user = Auth::user();

        $query = BudgetEntry::active()->whereYear('date', $this->selectedYear);

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

        // Gruppierte Stats: Zähle unique user_id + budget_name Kombinationen
        $entries = $query->get();
        $grouped = $entries->groupBy(fn($entry) => $entry->user_id . '|' . $entry->budget_name);

        $total = $grouped->count();
        $categorized = 0;
        $uncategorized = 0;
        $categoryA = 0;
        $categoryB = 0;
        $categoryC = 0;
        $categoryNone = 0;

        foreach ($grouped as $group) {
            $category = $group->first()->goal_category;
            
            if ($category === null) {
                $uncategorized++;
            } else {
                $categorized++;
                match ($category) {
                    'A' => $categoryA++,
                    'B' => $categoryB++,
                    'C' => $categoryC++,
                    'none' => $categoryNone++,
                    default => null,
                };
            }
        }

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

    /**
     * Kategorie für alle Einträge mit gleichem user_id + budget_name setzen
     */
    public function updateCategory(int $userId, string $budgetName, ?string $category): void
    {
        $user = Auth::user();

        // Berechtigungsprüfung
        if (!$this->showAllTeams) {
            $teamIds = $user->managedTeams()->pluck('teams.id');
            $targetUser = User::find($userId);
            if (!$targetUser || !$teamIds->contains($targetUser->team_id)) {
                return;
            }
        }

        // Alle Einträge mit diesem user_id + budget_name im aktuellen Jahr updaten
        BudgetEntry::where('user_id', $userId)
            ->where('budget_name', $budgetName)
            ->whereYear('date', $this->selectedYear)
            ->active()
            ->update(['goal_category' => $category ?: null]);
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
