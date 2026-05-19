<?php

namespace App\Livewire\Admin;

use App\Models\EmployeeBudgetRule;
use App\Models\Team;
use App\Models\User;
use App\Services\EmployeeBudgetCategoryService;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeeBudgetAssignment extends Component
{
    use WithPagination;

    public string $search = '';
    public ?int $filterTeam = null;
    public ?string $filterRule = null;
    public bool $showManualOnly = false;
    
    public bool $showAssignModal = false;
    public ?int $assigningUserId = null;
    public ?int $selectedRuleId = null;

    protected EmployeeBudgetCategoryService $budgetCategoryService;

    public function boot(EmployeeBudgetCategoryService $budgetCategoryService): void
    {
        $this->budgetCategoryService = $budgetCategoryService;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFilterTeam(): void
    {
        $this->resetPage();
    }

    public function updatedFilterRule(): void
    {
        $this->resetPage();
    }

    public function openAssignModal(int $userId): void
    {
        $this->assigningUserId = $userId;
        $user = User::find($userId);
        $this->selectedRuleId = $user?->employee_budget_rule_id;
        $this->showAssignModal = true;
    }

    public function closeAssignModal(): void
    {
        $this->showAssignModal = false;
        $this->assigningUserId = null;
        $this->selectedRuleId = null;
    }

    public function assignRule(): void
    {
        if (!$this->assigningUserId) {
            return;
        }

        $user = User::find($this->assigningUserId);
        if (!$user) {
            return;
        }

        $ruleId = $this->selectedRuleId ?: null;
        $user->update(['employee_budget_rule_id' => $ruleId]);

        session()->flash('message', $ruleId 
            ? 'Manuelle Zuordnung gespeichert.' 
            : 'Manuelle Zuordnung entfernt (automatische Ermittlung aktiv).');

        $this->closeAssignModal();
    }

    public function removeManualAssignment(int $userId): void
    {
        User::where('id', $userId)->update(['employee_budget_rule_id' => null]);
        session()->flash('message', 'Manuelle Zuordnung entfernt.');
    }

    public function render()
    {
        $query = User::active()
            ->whereNotNull('personio_id')
            ->with(['team', 'employeeBudgetRule']);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%')
                  ->orWhere('personio_position', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filterTeam) {
            $query->where('team_id', $this->filterTeam);
        }

        if ($this->showManualOnly) {
            $query->whereNotNull('employee_budget_rule_id');
        }

        $users = $query->orderBy('name')->paginate(25);

        $usersWithRules = $users->getCollection()->map(function (User $user) {
            $effectiveRule = $this->budgetCategoryService->determineBaseRuleForUser($user);
            $overlayRules = $this->budgetCategoryService->findMatchingOverlayRules($user);
            $maxCash = $this->budgetCategoryService->calculateCashLimit($overlayRules, $effectiveRule);
            
            return [
                'user' => $user,
                'effective_rule' => $effectiveRule,
                'overlay_rules' => $overlayRules,
                'is_manual' => $user->employee_budget_rule_id !== null,
                'budget' => $effectiveRule?->max_money_budget ?? 3000.00,
                'max_cash' => $maxCash,
                'has_cash_limit' => $maxCash !== null,
            ];
        });

        $users->setCollection($usersWithRules);

        $teams = Team::orderBy('name')->get();
        $rules = EmployeeBudgetRule::active()->base()->byPriority()->get();

        $stats = [
            'total' => User::active()->whereNotNull('personio_id')->count(),
            'manual' => User::active()->whereNotNull('personio_id')->whereNotNull('employee_budget_rule_id')->count(),
        ];

        return view('livewire.admin.employee-budget-assignment', [
            'users' => $users,
            'teams' => $teams,
            'rules' => $rules,
            'stats' => $stats,
        ]);
    }
}
