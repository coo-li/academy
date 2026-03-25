<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const NEW_CATEGORIES = [
        [
            'name' => 'Fachexpertise',
            'emoji' => '🔍',
            'description' => 'Channel- & Daten-Know-how: Kanalspezifisches Fachwissen, Datenanalyse und Funnel-Verständnis.',
            'sort_order' => 1,
            'old_names' => ['Analyse', 'Lead Gen', 'ECom', 'Recruiting', 'Brand', 'Organic'],
        ],
        [
            'name' => 'Strategie & Business',
            'emoji' => '💡',
            'description' => 'Strategisches Denken & Geschäftsverständnis: Big-Picture-Denken, Geschäftsmodelle, Marktverständnis und Skalierung.',
            'sort_order' => 2,
            'old_names' => ['Strategie', 'Business', 'Markt', 'Consulting', 'Growth', 'Business Development & Growth', 'Digitalstrategie'],
        ],
        [
            'name' => 'Kommunikation & Beziehungen',
            'emoji' => '🤝',
            'description' => 'Kunden- & Stakeholder-Kompetenz: Kommunikation, Erwartungsmanagement und Beziehungsaufbau.',
            'sort_order' => 3,
            'old_names' => ['Kommunikation', 'Kommunikation & Beratung', 'Erwartungsmanagement', 'Beziehungsmanagement'],
        ],
        [
            'name' => 'Führung & People Development',
            'emoji' => '⭐',
            'description' => 'Menschenführung & Teamentwicklung: Leadership-Mindset, Mitarbeiterförderung, Teamsteuerung und People Management.',
            'sort_order' => 4,
            'old_names' => ['Führungskompetenz', 'MA-Führung- & Entwicklung', 'Teamführung- & Entwicklung'],
        ],
        [
            'name' => 'Methodik & Organisation',
            'emoji' => '⚙️',
            'description' => 'Arbeitsweisen, Tools & Selbstmanagement: Methodische Kompetenz, Arbeitsorganisation, KPI-Verständnis und Projektsteuerung.',
            'sort_order' => 5,
            'old_names' => ['Methodik', 'Persönliche Entwicklung', 'Projektsteuerung & Projektmanagement', 'Team-KPIs'],
        ],
    ];

    public function up(): void
    {
        foreach (self::NEW_CATEGORIES as $category) {
            $oldNames = $category['old_names'];
            unset($category['old_names']);

            $newCategory = DB::table('skill_categories')
                ->where('name', $category['name'])
                ->first();

            if (! $newCategory) {
                $categoryId = DB::table('skill_categories')->insertGetId(array_merge($category, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            } else {
                $categoryId = $newCategory->id;
                DB::table('skill_categories')
                    ->where('id', $categoryId)
                    ->update([
                        'emoji' => $category['emoji'],
                        'description' => $category['description'],
                        'sort_order' => $category['sort_order'],
                        'updated_at' => now(),
                    ]);
            }

            $oldIds = DB::table('skill_categories')
                ->whereIn('name', $oldNames)
                ->pluck('id');

            if ($oldIds->isNotEmpty()) {
                DB::table('modules')
                    ->whereIn('skill_category_id', $oldIds)
                    ->update(['skill_category_id' => $categoryId]);

                DB::table('skill_categories')
                    ->whereIn('id', $oldIds)
                    ->delete();
            }
        }
    }

    public function down(): void
    {
        // Reverse is not feasible because the original granular assignment is lost.
        // The old categories would need to be re-imported from CSV.
    }
};
