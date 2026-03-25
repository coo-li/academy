<?php

namespace Database\Seeders;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\Role;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Admin User ---
        $admin = User::updateOrCreate(
            ['email' => 'admin@trafficdesign.de'],
            [
                'name' => 'td Admin',
                'password' => Hash::make('TdAcademy2026!'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        $adminRole = Role::where('slug', 'admin')->first();
        if ($adminRole) {
            $admin->roles()->syncWithoutDetaching([$adminRole->id]);
        }

        // --- Beispiel-Student ---
        $student = User::updateOrCreate(
            ['email' => 'student@trafficdesign.de'],
            [
                'name' => 'Max Mustermann',
                'password' => Hash::make('Student2026!'),
                'role' => 'student',
                'email_verified_at' => now(),
            ]
        );
        $mitarbeitenderRole = Role::where('slug', 'mitarbeitender')->first();
        if ($mitarbeitenderRole) {
            $student->roles()->syncWithoutDetaching([$mitarbeitenderRole->id]);
        }

        // --- Karrierepfad: SEO ---
        $seoPath = CareerPath::updateOrCreate(
            ['name' => 'SEO'],
            ['description' => 'Karrierepfad für Suchmaschinenoptimierung – vom Junior SEO bis zum Head of SEO.']
        );

        // Level 1: Junior SEO
        $juniorLevel = CareerLevel::updateOrCreate(
            ['career_path_id' => $seoPath->id, 'level_number' => 1],
            [
                'title' => 'Junior SEO',
                'description' => 'Einstieg in die Suchmaschinenoptimierung. Grundlagen und erste praktische Erfahrungen.',
            ]
        );

        // Level 2: Mid-Level SEO
        $midLevel = CareerLevel::updateOrCreate(
            ['career_path_id' => $seoPath->id, 'level_number' => 2],
            [
                'title' => 'Mid-Level SEO',
                'description' => 'Vertiefung in technisches SEO, Content-Strategie und eigenständige Projektarbeit.',
            ]
        );

        // Level 3: Senior SEO
        CareerLevel::updateOrCreate(
            ['career_path_id' => $seoPath->id, 'level_number' => 3],
            [
                'title' => 'Senior SEO',
                'description' => 'Spezialisierung, Mentoring und strategische Verantwortung.',
            ]
        );

        // --- Skill-Kategorien ---
        $fachexpertise = SkillCategory::firstOrCreate(
            ['name' => 'Fachexpertise'],
            ['emoji' => '🔍', 'description' => 'Channel- & Daten-Know-how', 'sort_order' => 1]
        );
        SkillCategory::firstOrCreate(
            ['name' => 'Strategie & Business'],
            ['emoji' => '💡', 'description' => 'Strategisches Denken & Geschäftsverständnis', 'sort_order' => 2]
        );
        SkillCategory::firstOrCreate(
            ['name' => 'Kommunikation & Beziehungen'],
            ['emoji' => '🤝', 'description' => 'Kunden- & Stakeholder-Kompetenz', 'sort_order' => 3]
        );
        SkillCategory::firstOrCreate(
            ['name' => 'Führung & People Development'],
            ['emoji' => '⭐', 'description' => 'Menschenführung & Teamentwicklung', 'sort_order' => 4]
        );
        SkillCategory::firstOrCreate(
            ['name' => 'Methodik & Organisation'],
            ['emoji' => '⚙️', 'description' => 'Arbeitsweisen, Tools & Selbstmanagement', 'sort_order' => 5]
        );

        // --- Module für Junior SEO ---
        $kwModule = Module::updateOrCreate(
            ['career_level_id' => $juniorLevel->id, 'title' => 'Keyword-Recherche Grundlagen'],
            [
                'description' => 'Lerne die Grundlagen der Keyword-Recherche: Tools, Suchintention, Keyword-Clustering und Priorisierung.',
                'type' => 'workshop',
                'is_mandatory' => true,
                'sort_order' => 1,
                'skill_category_id' => $fachexpertise->id,
            ]
        );

        Module::updateOrCreate(
            ['career_level_id' => $juniorLevel->id, 'title' => 'OnPage-Optimierung Basics'],
            [
                'description' => 'Title-Tags, Meta-Descriptions, Überschriftenstruktur, interne Verlinkung.',
                'type' => 'self_study',
                'is_mandatory' => true,
                'sort_order' => 2,
                'skill_category_id' => $fachexpertise->id,
            ]
        );

        Module::updateOrCreate(
            ['career_level_id' => $juniorLevel->id, 'title' => 'Google Search Console Einführung'],
            [
                'description' => 'Einrichtung, wichtigste Reports, Performance-Analyse und Fehlerdiagnose.',
                'type' => 'video',
                'is_mandatory' => true,
                'sort_order' => 3,
                'skill_category_id' => $fachexpertise->id,
            ]
        );

        Module::updateOrCreate(
            ['career_level_id' => $juniorLevel->id, 'title' => 'Erstes Kundenprojekt (Begleitung)'],
            [
                'description' => 'Begleitetes erstes SEO-Audit unter Anleitung eines Seniors.',
                'type' => 'one_on_one',
                'is_mandatory' => true,
                'sort_order' => 4,
                'skill_category_id' => $fachexpertise->id,
            ]
        );

        // --- Module für Mid-Level SEO ---
        Module::updateOrCreate(
            ['career_level_id' => $midLevel->id, 'title' => 'Technisches SEO'],
            [
                'description' => 'Crawling, Indexierung, Core Web Vitals, Schema Markup, Hreflang.',
                'type' => 'workshop',
                'is_mandatory' => true,
                'sort_order' => 1,
                'skill_category_id' => $fachexpertise->id,
            ]
        );

        Module::updateOrCreate(
            ['career_level_id' => $midLevel->id, 'title' => 'Content-Strategie'],
            [
                'description' => 'Content-Audit, Redaktionsplanung, E-E-A-T, Content-Formate.',
                'type' => 'self_study',
                'is_mandatory' => true,
                'sort_order' => 2,
                'skill_category_id' => $fachexpertise->id,
            ]
        );

        // --- Quiz für Keyword-Recherche ---
        Quiz::updateOrCreate(
            ['module_id' => $kwModule->id],
            [
                'pass_percentage' => 70,
                'questions' => [
                    [
                        'question' => 'Was beschreibt die "Suchintention" eines Keywords?',
                        'options' => [
                            'Die Häufigkeit, mit der ein Keyword gesucht wird',
                            'Die Absicht des Nutzers hinter einer Suchanfrage',
                            'Die Position eines Keywords in den SERPs',
                            'Die Anzahl der Wörter in einem Keyword',
                        ],
                        'correct' => 1,
                    ],
                    [
                        'question' => 'Welches Tool eignet sich NICHT primär für Keyword-Recherche?',
                        'options' => [
                            'Google Keyword Planner',
                            'Ahrefs',
                            'Google PageSpeed Insights',
                            'SEMrush',
                        ],
                        'correct' => 2,
                    ],
                    [
                        'question' => 'Was ist "Keyword-Clustering"?',
                        'options' => [
                            'Die Gruppierung thematisch verwandter Keywords',
                            'Das Entfernen irrelevanter Keywords',
                            'Die Analyse der Keyword-Schwierigkeit',
                            'Die automatische Keyword-Generierung',
                        ],
                        'correct' => 0,
                    ],
                ],
            ]
        );
    }
}
