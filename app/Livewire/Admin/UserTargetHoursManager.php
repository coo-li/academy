<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UserTargetHoursManager extends Component
{
    use WithPagination;

    public string $search = '';
    public array $targetHours = [];
    public bool $showSuccess = false;
    public int $year;

    public function mount(): void
    {
        $this->year = date('Y');
        $this->loadTargetHours();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function loadTargetHours(): void
    {
        $users = $this->getFilteredUsers();
        
        foreach ($users as $user) {
            $this->targetHours[$user->id] = $user->target_hours_per_year ?? 0;
        }
    }

    public function saveTargetHours(): void
    {
        foreach ($this->targetHours as $userId => $hours) {
            $numericHours = floatval(str_replace(',', '.', $hours ?? 0));
            
            User::where('id', $userId)->update([
                'target_hours_per_year' => $numericHours,
            ]);
        }

        $this->showSuccess = true;
        $this->dispatch('target-hours-saved');
    }

    public function setAllToDefault(float $defaultHours = 62): void
    {
        $userIds = $this->getFilteredUsers()->pluck('id');
        
        User::whereIn('id', $userIds)->update([
            'target_hours_per_year' => $defaultHours,
        ]);

        foreach ($userIds as $id) {
            $this->targetHours[$id] = $defaultHours;
        }

        $this->showSuccess = true;
    }

    protected function getFilteredUsers()
    {
        $query = User::with('team')
            ->whereNull('archived_at')
            ->orderBy('name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        return $query->get();
    }

    public function render()
    {
        $users = $this->getFilteredUsers();
        
        $stats = [
            'total' => $users->count(),
            'with_hours' => $users->where('target_hours_per_year', '>', 0)->count(),
            'without_hours' => $users->where('target_hours_per_year', '<=', 0)->count(),
            'total_hours' => $users->sum('target_hours_per_year'),
        ];

        return view('livewire.admin.user-target-hours-manager', [
            'users' => $users,
            'stats' => $stats,
        ]);
    }
}
