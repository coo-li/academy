<?php

namespace App\Livewire\Admin;

use App\Models\CareerLevel;
use App\Models\CareerLevelRate;
use Livewire\Component;
use Livewire\Attributes\On;

class CareerLevelRatesManager extends Component
{
    public array $hourlyRates = [];
    public bool $showSuccess = false;

    public function mount(): void
    {
        $this->loadRates();
    }

    public function loadRates(): void
    {
        $this->hourlyRates = [];
        
        $levels = CareerLevel::with('rate')->get();

        foreach ($levels as $level) {
            $this->hourlyRates[(string) $level->id] = $level->rate?->hourly_rate !== null 
                ? (string) $level->rate->hourly_rate 
                : '';
        }
    }

    public function updateRate($levelId, $value): void
    {
        $this->hourlyRates[(string) $levelId] = $value;
    }

    public function saveRates(): void
    {
        \Log::info('saveRates called', ['hourlyRates' => $this->hourlyRates]);
        
        $saved = 0;
        $levels = CareerLevel::all();

        foreach ($levels as $level) {
            $key = (string) $level->id;
            $rate = $this->hourlyRates[$key] ?? null;
            
            if ($rate === '' || $rate === null) {
                CareerLevelRate::where('career_level_id', $level->id)->delete();
                continue;
            }

            $numericRate = floatval(str_replace(',', '.', (string) $rate));
            
            if ($numericRate > 0) {
                CareerLevelRate::updateOrCreate(
                    ['career_level_id' => $level->id],
                    ['hourly_rate' => $numericRate]
                );
                $saved++;
            }
        }

        \Log::info('CareerLevelRates saved', ['saved' => $saved]);

        $this->showSuccess = true;
        
        $this->dispatch('rates-saved');
    }

    public function render()
    {
        $levels = CareerLevel::with(['careerPath', 'rate'])
            ->orderBy('career_path_id')
            ->orderBy('level_number')
            ->get();

        $groupedLevels = $levels->groupBy(fn($level) => $level->careerPath?->name ?? 'Unbekannt');
        
        return view('livewire.admin.career-level-rates-manager', [
            'groupedLevels' => $groupedLevels,
        ]);
    }
}
