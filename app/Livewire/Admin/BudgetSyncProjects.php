<?php

namespace App\Livewire\Admin;

use App\Models\BudgetEntry;
use App\Models\BudgetSyncProject;
use Livewire\Component;
use Livewire\WithPagination;

class BudgetSyncProjects extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    
    // Formular für neues Projekt
    public bool $showAddForm = false;
    public string $newProjectId = '';
    public string $newProjectName = '';
    public string $newCustomerName = 'trafficdesign';
    
    // Flash messages
    public string $message = '';
    public string $messageType = 'success';

    protected $rules = [
        'newProjectId' => 'required|string|unique:budget_sync_projects,project_id',
        'newProjectName' => 'required|string|max:255',
        'newCustomerName' => 'nullable|string|max:255',
    ];

    public function getProjectsProperty()
    {
        $query = BudgetSyncProject::query();
        
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('project_id', 'like', "%{$this->search}%")
                  ->orWhere('project_name', 'like', "%{$this->search}%")
                  ->orWhere('customer_name', 'like', "%{$this->search}%");
            });
        }
        
        if ($this->statusFilter === 'active') {
            $query->active();
        } elseif ($this->statusFilter === 'inactive') {
            $query->inactive();
        }
        
        return $query->orderBy('project_name')->paginate(20);
    }

    public function getStatsProperty(): array
    {
        return [
            'total' => BudgetSyncProject::count(),
            'active' => BudgetSyncProject::active()->count(),
            'inactive' => BudgetSyncProject::inactive()->count(),
        ];
    }

    public function toggleStatus(int $id): void
    {
        $project = BudgetSyncProject::find($id);
        if ($project) {
            $project->update(['is_active' => !$project->is_active]);
            $this->showMessage(
                $project->is_active ? 'Projekt aktiviert.' : 'Projekt deaktiviert.',
                'success'
            );
        }
    }

    public function addProject(): void
    {
        $this->validate();
        
        BudgetSyncProject::create([
            'project_id' => $this->newProjectId,
            'project_name' => $this->newProjectName,
            'customer_name' => $this->newCustomerName ?: null,
            'is_active' => true,
        ]);
        
        $this->reset(['newProjectId', 'newProjectName', 'showAddForm']);
        $this->newCustomerName = 'trafficdesign';
        $this->showMessage('Projekt hinzugefügt.', 'success');
    }

    public function deleteProject(int $id): void
    {
        $project = BudgetSyncProject::find($id);
        if ($project) {
            $project->delete();
            $this->showMessage('Projekt entfernt.', 'success');
        }
    }

    public function importFromExistingEntries(): void
    {
        $existingProjectIds = BudgetEntry::whereNotNull('project_id')
            ->where('project_id', '!=', '')
            ->distinct()
            ->pluck('project_id');
        
        $imported = 0;
        foreach ($existingProjectIds as $projectId) {
            $created = BudgetSyncProject::firstOrCreate(
                ['project_id' => $projectId],
                [
                    'project_name' => 'Projekt ' . $projectId,
                    'customer_name' => 'trafficdesign',
                    'is_active' => true,
                ]
            );
            if ($created->wasRecentlyCreated) {
                $imported++;
            }
        }
        
        $this->showMessage("{$imported} Projekte aus bestehenden Einträgen importiert.", 'success');
    }

    protected function showMessage(string $message, string $type = 'success'): void
    {
        $this->message = $message;
        $this->messageType = $type;
    }

    public function dismissMessage(): void
    {
        $this->message = '';
    }

    public function render()
    {
        return view('livewire.admin.budget-sync-projects', [
            'projects' => $this->projects,
            'stats' => $this->stats,
        ])->layout('layouts.app');
    }
}
