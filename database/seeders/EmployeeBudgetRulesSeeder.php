<?php

namespace Database\Seeders;

use App\Models\EmployeeBudgetRule;
use Illuminate\Database\Seeder;

class EmployeeBudgetRulesSeeder extends Seeder
{
    public function run(): void
    {
        $baseRules = [
            [
                'name' => 'Standard',
                'slug' => 'standard',
                'description' => 'Standard-Weiterbildungsbudget für alle Vollzeit-Mitarbeiter',
                'rule_type' => EmployeeBudgetRule::TYPE_BASE,
                'max_money_budget' => 3000.00,
                'priority' => 1,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Trainee / Praktikant',
                'slug' => 'trainee',
                'description' => 'Budget für Trainees, Praktikanten, Auszubildende und Dualstudenten',
                'rule_type' => EmployeeBudgetRule::TYPE_BASE,
                'max_money_budget' => 0.00,
                'hourly_rate_override' => 70.00,
                'priority' => 20,
                'is_default' => false,
                'is_active' => true,
                'applies_to_positions' => ['Trainee', 'Praktikant', 'Azubi', 'Auszubildende', 'Dualstudent', 'Werkstudent'],
            ],
            [
                'name' => 'Teilzeit unter 20h',
                'slug' => 'teilzeit-unter-20',
                'description' => 'Reduziertes Budget für Teilzeitkräfte mit weniger als 20 Wochenstunden',
                'rule_type' => EmployeeBudgetRule::TYPE_BASE,
                'max_money_budget' => 1000.00,
                'priority' => 15,
                'is_default' => false,
                'is_active' => true,
                'working_hours_min' => 0,
                'working_hours_max' => 19.99,
            ],
            [
                'name' => 'Teilzeit 20-30h',
                'slug' => 'teilzeit-20-30',
                'description' => 'Angepasstes Budget für Teilzeitkräfte mit 20-30 Wochenstunden',
                'rule_type' => EmployeeBudgetRule::TYPE_BASE,
                'max_money_budget' => 2000.00,
                'priority' => 14,
                'is_default' => false,
                'is_active' => true,
                'working_hours_min' => 20,
                'working_hours_max' => 30,
            ],
        ];

        $overlayRules = [
            [
                'name' => 'Overhead / Backoffice',
                'slug' => 'overhead',
                'description' => 'Cash-Limit für Overhead-Bereiche (Backoffice, HR, Finance, etc.) - limitiert nur das maximale Geld-Budget, nicht das Gesamt-Budget',
                'rule_type' => EmployeeBudgetRule::TYPE_OVERLAY,
                'max_cash_budget' => 1500.00,
                'max_money_budget' => null,
                'priority' => 10,
                'is_default' => false,
                'is_active' => true,
                'applies_to_departments' => ['Backoffice', 'HR', 'Finance', 'Administration'],
            ],
        ];

        foreach (array_merge($baseRules, $overlayRules) as $ruleData) {
            EmployeeBudgetRule::updateOrCreate(
                ['slug' => $ruleData['slug']],
                $ruleData
            );
        }
    }
}
