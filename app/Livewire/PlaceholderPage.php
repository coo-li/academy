<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Route;
use Livewire\Component;

class PlaceholderPage extends Component
{
    public string $routeName = '';

    public function mount(): void
    {
        $this->routeName = Route::currentRouteName() ?? 'unknown';
    }

    public function render()
    {
        return view('livewire.placeholder-page')
            ->layout('layouts.app');
    }
}
