<?php

namespace App\Livewire\Admin;

use App\Models\EmployeeBudgetRule;
use App\Services\EmployeeBudgetCategoryService;
use Livewire\Component;

class BudgetRulesManager extends Component
{
    public bool $showForm = false;
    public bool $editMode = false;
    public ?int $editingId = null;
    
    public string $name = '';
    public string $description = '';
    public string $rule_type = 'base';
    public ?float $max_money_budget = 3000.00;
    public ?float $max_cash_budget = null;
    public ?float $time_money_ratio = null;
    public ?float $hourly_rate_override = null;
    public ?float $working_hours_min = null;
    public ?float $working_hours_max = null;
    public int $priority = 0;
    public bool $is_default = false;
    public array $applies_to_departments = [];
    public array $applies_to_positions = [];
    public array $applies_to_levels = [];
    public bool $is_active = true;
    
    public string $newDepartment = '';
    public string $newPosition = '';
    public string $newLevel = '';

    protected EmployeeBudgetCategoryService $budgetCategoryService;

    public function boot(EmployeeBudgetCategoryService $budgetCategoryService): void
    {
        $this->budgetCategoryService = $budgetCategoryService;
    }

    protected function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'rule_type' => 'required|in:base,overlay',
            'priority' => 'required|integer|min:0',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'working_hours_min' => 'nullable|numeric|min:0|max:168',
            'working_hours_max' => 'nullable|numeric|min:0|max:168',
        ];

        if ($this->rule_type === EmployeeBudgetRule::TYPE_BASE) {
            $rules['max_money_budget'] = 'required|numeric|min:0';
            $rules['time_money_ratio'] = 'nullable|numeric|min:0';
            $rules['hourly_rate_override'] = 'nullable|numeric|min:0';
        } else {
            $rules['max_cash_budget'] = 'required|numeric|min:0';
        }

        return $rules;
    }

    public function openCreate(): void
    {
        $this->reset(['name', 'description', 'rule_type', 'max_money_budget', 'max_cash_budget',
            'time_money_ratio', 'hourly_rate_override', 'working_hours_min', 'working_hours_max', 
            'priority', 'is_default', 'applies_to_departments', 'applies_to_positions',
            'applies_to_levels', 'is_active', 'editingId', 'editMode']);
        $this->rule_type = EmployeeBudgetRule::TYPE_BASE;
        $this->max_money_budget = 3000.00;
        $this->max_cash_budget = null;
        $this->is_active = true;
        $this->showForm = true;
        $this->editMode = false;
    }

    public function edit(int $id): void
    {
        $rule = EmployeeBudgetRule::findOrFail($id);
        
        $this->editingId = $id;
        $this->name = $rule->name;
        $this->description = $rule->description ?? '';
        $this->rule_type = $rule->rule_type ?? EmployeeBudgetRule::TYPE_BASE;
        $this->max_money_budget = $rule->max_money_budget !== null ? (float) $rule->max_money_budget : null;
        $this->max_cash_budget = $rule->max_cash_budget !== null ? (float) $rule->max_cash_budget : null;
        $this->time_money_ratio = $rule->time_money_ratio ? (float) $rule->time_money_ratio : null;
        $this->hourly_rate_override = $rule->hourly_rate_override ? (float) $rule->hourly_rate_override : null;
        $this->working_hours_min = $rule->working_hours_min ? (float) $rule->working_hours_min : null;
        $this->working_hours_max = $rule->working_hours_max ? (float) $rule->working_hours_max : null;
        $this->priority = (int) $rule->priority;
        $this->is_default = (bool) $rule->is_default;
        $this->applies_to_departments = $rule->applies_to_departments ?? [];
        $this->applies_to_positions = $rule->applies_to_positions ?? [];
        $this->applies_to_levels = $rule->applies_to_levels ?? [];
        $this->is_active = (bool) $rule->is_active;
        
        $this->showForm = true;
        $this->editMode = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'rule_type' => $this->rule_type,
            'working_hours_min' => $this->working_hours_min,
            'working_hours_max' => $this->working_hours_max,
            'priority' => $this->priority,
            'is_default' => $this->rule_type === EmployeeBudgetRule::TYPE_BASE ? $this->is_default : false,
            'applies_to_departments' => array_values(array_filter($this->applies_to_departments)),
            'applies_to_positions' => array_values(array_filter($this->applies_to_positions)),
            'applies_to_levels' => array_values(array_filter($this->applies_to_levels)),
            'is_active' => $this->is_active,
        ];

        if ($this->rule_type === EmployeeBudgetRule::TYPE_BASE) {
            $data['max_money_budget'] = $this->max_money_budget;
            $data['max_cash_budget'] = null;
            $data['time_money_ratio'] = $this->time_money_ratio;
            $data['hourly_rate_override'] = $this->hourly_rate_override;
        } else {
            $data['max_money_budget'] = null;
            $data['max_cash_budget'] = $this->max_cash_budget;
            $data['time_money_ratio'] = null;
            $data['hourly_rate_override'] = null;
        }

        if ($this->is_default && $this->rule_type === EmployeeBudgetRule::TYPE_BASE) {
            EmployeeBudgetRule::where('is_default', true)
                ->where('id', '!=', $this->editingId)
                ->update(['is_default' => false]);
        }

        if ($this->editMode && $this->editingId) {
            EmployeeBudgetRule::where('id', $this->editingId)->update($data);
            session()->flash('message', 'Regel erfolgreich aktualisiert.');
        } else {
            EmployeeBudgetRule::create($data);
            session()->flash('message', 'Regel erfolgreich erstellt.');
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        $rule = EmployeeBudgetRule::find($id);
        if ($rule && !$rule->is_default) {
            $rule->delete();
            session()->flash('message', 'Regel gelöscht.');
        } else {
            session()->flash('error', 'Standard-Regel kann nicht gelöscht werden.');
        }
    }

    public function toggleActive(int $id): void
    {
        $rule = EmployeeBudgetRule::find($id);
        if ($rule) {
            $rule->update(['is_active' => !$rule->is_active]);
        }
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->reset(['editingId', 'editMode', 'name', 'description', 'newDepartment', 'newPosition', 'newLevel']);
    }

    public function addDepartment(): void
    {
        if (trim($this->newDepartment) !== '') {
            $this->applies_to_departments[] = trim($this->newDepartment);
            $this->newDepartment = '';
        }
    }

    public function removeDepartment(int $index): void
    {
        unset($this->applies_to_departments[$index]);
        $this->applies_to_departments = array_values($this->applies_to_departments);
    }

    public function addPosition(): void
    {
        if (trim($this->newPosition) !== '') {
            $this->applies_to_positions[] = trim($this->newPosition);
            $this->newPosition = '';
        }
    }

    public function removePosition(int $index): void
    {
        unset($this->applies_to_positions[$index]);
        $this->applies_to_positions = array_values($this->applies_to_positions);
    }

    public function addLevel(): void
    {
        if (trim($this->newLevel) !== '') {
            $this->applies_to_levels[] = trim($this->newLevel);
            $this->newLevel = '';
        }
    }

    public function removeLevel(int $index): void
    {
        unset($this->applies_to_levels[$index]);
        $this->applies_to_levels = array_values($this->applies_to_levels);
    }

    public function syncBudgets(): void
    {
        $year = (int) date('Y');
        $stats = $this->budgetCategoryService->syncAllUserBudgets($year);
        
        session()->flash('message', sprintf(
            'Budgets synchronisiert: %d erstellt, %d aktualisiert, %d unverändert',
            $stats['created'],
            $stats['updated'],
            $stats['unchanged']
        ));
    }

    public function render()
    {
        $allRules = EmployeeBudgetRule::orderByDesc('priority')->get();
        $baseRules = $allRules->filter(fn ($r) => $r->isBase());
        $overlayRules = $allRules->filter(fn ($r) => $r->isOverlay());
        
        $statistics = $this->budgetCategoryService->getRuleStatistics();
        $overlayStatistics = $this->budgetCategoryService->getOverlayStatistics();

        return view('livewire.admin.budget-rules-manager', [
            'rules' => $allRules,
            'baseRules' => $baseRules,
            'overlayRules' => $overlayRules,
            'statistics' => $statistics,
            'overlayStatistics' => $overlayStatistics,
        ]);
    }
}
