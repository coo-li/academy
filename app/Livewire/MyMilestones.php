<?php

namespace App\Livewire;

use App\Models\Milestone;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class MyMilestones extends Component
{
    public function getMilestonesByCategoryProperty()
    {
        $user = Auth::user();
        $disabledMilestoneIds = $user->disabledMilestones->pluck('id')->toArray();

        return Milestone::forUser($user)
            ->orderBy('sort_order')
            ->get()
            ->reject(fn ($m) => in_array($m->id, $disabledMilestoneIds))
            ->groupBy('category');
    }

    public function getUserProperty()
    {
        return Auth::user();
    }

    public function render()
    {
        return view('livewire.my-milestones', [
            'milestonesByCategory' => $this->milestonesByCategory,
            'user' => $this->user,
        ])->layout('layouts.app');
    }
}
