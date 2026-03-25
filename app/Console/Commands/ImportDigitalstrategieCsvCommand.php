<?php

namespace App\Console\Commands;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Module;
use App\Models\SkillCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDigitalstrategieCsvCommand extends Command
{
    protected $signature = 'academy:import-digitalstrategie-csv
        {--dry-run : Preview without writing to database}';

    protected $description = 'Import Digitalstrategie Karrierepfad modules from CSV';

    private const SKILL_CATEGORY_MAP = [
        'Analyse'    => 'Fachexpertise',
        'Lead Gen'   => 'Fachexpertise',
        'ECom'       => 'Fachexpertise',
        'Recruiting' => 'Fachexpertise',
        'Brand'      => 'Fachexpertise',
        'Organic'    => 'Fachexpertise',
        'Strategie'  => 'Strategie & Business',
        'Business'   => 'Strategie & Business',
        'Markt'      => 'Strategie & Business',
        'Consulting' => 'Strategie & Business',
        'Growth'     => 'Strategie & Business',
        'Kommunikation' => 'Kommunikation & Beziehungen',
        'Methodik'   => 'Methodik & Organisation',
    ];

    private array $stats = [
        'paths_created' => 0,
        'levels_created' => 0,
        'modules_created' => 0,
        'modules_skipped' => 0,
        'categories_created' => 0,
    ];

    private const LEVEL_NUMBERS = [
        'TD Allgemein' => [
            'Junior' => 2,
            'Professional' => 3,
        ],
        'Expert (Digitalstrategie)' => [
            'Specialist' => 1,
            'Senior' => 2,
        ],
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->components->warn('DRY-RUN Modus – es werden keine Daten geschrieben.');
        }

        $file = storage_path('imports/Expert_Digitalstrategen_Path.csv');

        if (! file_exists($file)) {
            $this->components->error('CSV-Datei nicht gefunden: storage/imports/Expert_Digitalstrategen_Path.csv');
            return self::FAILURE;
        }

        $rows = $this->parseCsv($file);
        $this->info(count($rows) . ' Zeilen geladen');
        $this->newLine();

        $grouped = $this->groupByPathAndLevel($rows);

        if ($dryRun) {
            $this->previewImport($grouped);
            return self::SUCCESS;
        }

        DB::transaction(function () use ($grouped) {
            $this->executeImport($grouped);
        });

        $this->newLine();
        $this->components->info('Import abgeschlossen!');
        $this->table(
            ['Metrik', 'Anzahl'],
            [
                ['Karrierepfade erstellt', $this->stats['paths_created']],
                ['Karrierestufen erstellt', $this->stats['levels_created']],
                ['Module erstellt', $this->stats['modules_created']],
                ['Module übersprungen (Duplikate)', $this->stats['modules_skipped']],
                ['Skill-Kategorien erstellt', $this->stats['categories_created']],
            ]
        );

        return self::SUCCESS;
    }

    private function parseCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        $header = fgetcsv($handle);
        $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($header)) {
                $rows[] = array_combine($header, $data);
            }
        }

        fclose($handle);
        return $rows;
    }

    private function mapPathName(string $csvPfad): string
    {
        return match (trim($csvPfad)) {
            'td Allgemein' => 'TD Allgemein',
            'Expert/ Digitalstratege' => 'Expert (Digitalstrategie)',
            default => trim($csvPfad),
        };
    }

    private function stripEmoji(string $text): string
    {
        $cleaned = preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F000}-\x{1FFFF}\x{200D}\x{20E3}\x{E0020}-\x{E007F}\x{1FA70}-\x{1FAFF}\x{2700}-\x{27BF}🩵]+\s*/u', '', $text);
        return trim($cleaned);
    }

    private function groupByPathAndLevel(array $rows): array
    {
        $grouped = [];

        foreach ($rows as $row) {
            $pathName = $this->mapPathName($row['Pfad'] ?? '');
            $level = trim($row['Karrierestufe'] ?? '');

            if (empty($pathName) || empty($level)) {
                continue;
            }

            $grouped[$pathName][$level][] = $row;
        }

        return $grouped;
    }

    private function previewImport(array $grouped): void
    {
        foreach ($grouped as $pathName => $levels) {
            $this->components->info("Karrierepfad: {$pathName}");

            foreach ($levels as $levelName => $modules) {
                $levelNumber = self::LEVEL_NUMBERS[$pathName][$levelName] ?? '?';
                $this->components->twoColumnDetail(
                    "  {$levelName} (#{$levelNumber})",
                    count($modules) . ' Module'
                );

                foreach ($modules as $row) {
                    $thema = trim($row['Thema'] ?? '');
                    $bereich = $this->stripEmoji(trim($row['Bereich'] ?? ''));
                    $this->line("    - {$thema} ({$bereich})");
                }
            }
            $this->newLine();
        }
    }

    private function executeImport(array $grouped): void
    {
        foreach ($grouped as $pathName => $levels) {
            $path = CareerPath::firstOrCreate(
                ['name' => $pathName],
                ['description' => "Karrierepfad {$pathName}"]
            );

            if ($path->wasRecentlyCreated) {
                $this->stats['paths_created']++;
                $this->components->info("Karrierepfad erstellt: {$pathName}");
            } else {
                $this->components->info("Karrierepfad existiert bereits: {$pathName}");
            }

            foreach ($levels as $levelName => $modules) {
                $levelNumber = self::LEVEL_NUMBERS[$pathName][$levelName] ?? 99;

                $level = CareerLevel::firstOrCreate(
                    ['career_path_id' => $path->id, 'level_number' => $levelNumber],
                    ['title' => $levelName]
                );

                if ($level->wasRecentlyCreated) {
                    $this->stats['levels_created']++;
                    $this->line("  Stufe erstellt: {$levelName} (#{$levelNumber})");
                }

                $sortOrder = $level->modules()->max('sort_order') ?? 0;

                foreach ($modules as $row) {
                    $sortOrder++;
                    $title = trim($row['Thema'] ?? '');
                    $bereich = $this->stripEmoji(trim($row['Bereich'] ?? ''));
                    $inhalte = trim($row['Inhalte'] ?? '');
                    $ziel = trim($row['Ziel'] ?? '');

                    $description = $inhalte;
                    if ($ziel) {
                        $description .= "\n\nZiel: {$ziel}";
                    }

                    $skillCategoryId = null;
                    if ($bereich) {
                        $mappedName = self::SKILL_CATEGORY_MAP[$bereich] ?? $bereich;
                        $category = SkillCategory::firstOrCreate(['name' => $mappedName]);
                        $skillCategoryId = $category->id;
                        if ($category->wasRecentlyCreated) {
                            $this->stats['categories_created']++;
                        }
                    }

                    $module = Module::firstOrCreate(
                        ['career_level_id' => $level->id, 'title' => $title],
                        [
                            'description' => $description ?: null,
                            'skill_category_id' => $skillCategoryId,
                            'is_mandatory' => true,
                            'sort_order' => $sortOrder,
                        ]
                    );

                    if ($module->wasRecentlyCreated) {
                        $this->stats['modules_created']++;
                        $this->line("    + {$title}");
                    } else {
                        $this->stats['modules_skipped']++;
                        $this->line("    ~ {$title} (übersprungen)");
                    }
                }
            }
        }
    }
}
