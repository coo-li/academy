<?php

namespace Database\Seeders;

use App\Models\CareerLevel;
use App\Models\CareerLevelRate;
use Illuminate\Database\Seeder;

class CareerLevelRateSeeder extends Seeder
{
    protected array $rateMapping = [
        'junior' => 120.00,
        'professional' => 130.00,
        'specialist' => 150.00,
        'senior expert' => 180.00,
        'senior' => 160.00,
        'head of' => 160.00,
        'head' => 160.00,
        'trainee' => 90.00,
        'praktikant' => 50.00,
        'auszubildende' => 50.00,
        'azubi' => 50.00,
    ];

    protected float $defaultRate = 120.00;

    public function run(): void
    {
        $careerLevels = CareerLevel::all();
        $created = 0;
        $updated = 0;

        foreach ($careerLevels as $level) {
            $hourlyRate = $this->determineRate($level->title);

            $rate = CareerLevelRate::updateOrCreate(
                ['career_level_id' => $level->id],
                ['hourly_rate' => $hourlyRate]
            );

            if ($rate->wasRecentlyCreated) {
                $created++;
            } else {
                $updated++;
            }

            $this->command->info("  {$level->title}: {$hourlyRate} €/h");
        }

        $this->command->info("Career Level Rates: {$created} created, {$updated} updated");
    }

    protected function determineRate(string $title): float
    {
        $titleLower = mb_strtolower($title);

        foreach ($this->rateMapping as $keyword => $rate) {
            if (str_contains($titleLower, $keyword)) {
                return $rate;
            }
        }

        return $this->defaultRate;
    }
}
