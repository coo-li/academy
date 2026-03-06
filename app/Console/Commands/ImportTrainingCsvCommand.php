<?php

namespace App\Console\Commands;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Module;
use App\Models\SkillCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTrainingCsvCommand extends Command
{
    protected $signature = 'academy:import-csv
        {--dry-run : Preview without writing to database}
        {--update-descriptions : Update descriptions for existing modules from Notes}';

    protected $description = 'Import training modules from Asana CSV exports into the Strukturverwaltung';

    private array $stats = [
        'paths_created' => 0,
        'levels_created' => 0,
        'modules_created' => 0,
        'modules_skipped' => 0,
        'modules_updated' => 0,
        'rows_filtered' => 0,
    ];

    private const LEVEL_ORDER = [
        'Onboarding' => 1,
        'Junior' => 2,
        'Professional' => 3,
        'Specialist' => 1,
        'Senior' => 2,
        'Experte' => 3,
    ];

    private const FORMAT_TYPE_MAP = [
        'Workshop' => 'workshop',
        'Präsentation' => 'workshop',
        'Selbststudium (Notion)' => 'self_study',
        '1:1 Meeting' => 'one_on_one',
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->components->warn('DRY-RUN Modus – es werden keine Daten geschrieben.');
        }

        $leadershipFile = storage_path('imports/🚀_interne_Schulungen_Leadership_Path (1).csv');
        $amFile = storage_path('imports/🚀_interne_Schulungen_Accountmanagement_Path.csv');

        if (! file_exists($leadershipFile) || ! file_exists($amFile)) {
            $this->components->error('CSV-Dateien nicht gefunden in storage/imports/');
            return self::FAILURE;
        }

        $leadershipRows = $this->parseCsv($leadershipFile);
        $amRows = $this->parseCsv($amFile);

        $this->info("Leadership CSV: " . count($leadershipRows) . " Zeilen geladen");
        $this->info("Account Management CSV: " . count($amRows) . " Zeilen geladen");
        $this->newLine();

        $leadershipModules = $this->filterTopLevelModules($leadershipRows);
        $amModules = $this->filterTopLevelModules($amRows);

        $this->info("Leadership: " . count($leadershipModules) . " Module nach Filterung");
        $this->info("Account Management: " . count($amModules) . " Module nach Filterung");
        $this->info("Gefilterte Zeilen: " . $this->stats['rows_filtered']);
        $this->newLine();

        $grouped = $this->groupByPathAndLevel($leadershipModules, $amModules);

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
                ['Module Beschreibung aktualisiert', $this->stats['modules_updated']],
                ['Module übersprungen (Duplikate)', $this->stats['modules_skipped']],
            ]
        );

        return self::SUCCESS;
    }

    private function parseCsv(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'r');

        $header = fgetcsv($handle);
        // Strip BOM from first column header
        $header[0] = preg_replace('/^\x{FEFF}/u', '', $header[0]);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) === count($header)) {
                $rows[] = array_combine($header, $data);
            }
        }

        fclose($handle);
        return $rows;
    }

    private function filterTopLevelModules(array $rows): array
    {
        return array_values(array_filter($rows, function (array $row) {
            if (! empty(trim($row['Parent task'] ?? ''))) {
                $this->stats['rows_filtered']++;
                return false;
            }

            $section = trim($row['Section/Column'] ?? '');
            if (empty($section) || $section === 'Unbenannter Abschnitt') {
                $this->stats['rows_filtered']++;
                return false;
            }

            $name = trim($row['Name'] ?? '');
            if (empty($name) || stripos($name, 'IDEE:') !== false) {
                $this->stats['rows_filtered']++;
                return false;
            }

            return true;
        }));
    }

    /**
     * Group modules into the three career paths, deduplicating cross-references.
     *
     * Leadership modules that appear in the AM CSV as cross-references are skipped
     * because they're already covered by the Leadership CSV.
     */
    private function groupByPathAndLevel(array $leadershipModules, array $amModules): array
    {
        $paths = [
            'TD Allgemein' => [],
            'Leadership' => [],
            'Account Management' => [],
        ];

        foreach ($leadershipModules as $row) {
            $pathName = $this->extractPathFromSection($row['Section/Column'] ?? '');
            $level = trim($row['Karrierestufe'] ?? '');

            if (empty($level)) {
                continue;
            }

            if ($pathName === 'td Allgemein') {
                $paths['TD Allgemein'][$level][] = $row;
            } elseif ($pathName === 'Leadership') {
                $paths['Leadership'][$level][] = $row;
            }
        }

        $tdAllgemeinNames = $this->collectModuleNames($paths['TD Allgemein']);
        $leadershipNames = $this->collectModuleNames($paths['Leadership']);

        foreach ($amModules as $row) {
            $pathName = $this->extractPathFromSection($row['Section/Column'] ?? '');
            $level = trim($row['Karrierestufe'] ?? '');
            $cleanName = $this->cleanModuleName(trim($row['Name'] ?? ''));

            if (empty($level)) {
                continue;
            }

            if ($pathName === 'td Allgemein') {
                if (! in_array($level, ['Übergreifend', 'Onboarding', 'Junior', 'Professional'], true)) {
                    continue;
                }
                if (in_array($cleanName, $leadershipNames, true)) {
                    continue;
                }
                if (in_array($cleanName, $tdAllgemeinNames, true)) {
                    continue;
                }
                $paths['TD Allgemein'][$level][] = $row;
                $tdAllgemeinNames[] = $cleanName;
            } elseif ($pathName === 'Accountmanagement') {
                if (in_array($cleanName, $leadershipNames, true)) {
                    continue;
                }
                $paths['Account Management'][$level][] = $row;
            }
        }

        return $paths;
    }

    private function collectModuleNames(array $levelGroups): array
    {
        $names = [];
        foreach ($levelGroups as $modules) {
            foreach ($modules as $row) {
                $names[] = $this->cleanModuleName(trim($row['Name'] ?? ''));
            }
        }
        return $names;
    }

    private function extractPathFromSection(string $section): string
    {
        $section = trim($section);

        // Handle "new ✨ - Accountmanagement"
        $section = preg_replace('/^new\s*✨?\s*-\s*/u', '', $section);

        // Split on " - " or " – " (dash or en-dash)
        $parts = preg_split('/\s*[–\-]\s*/u', $section, 2);

        return trim($parts[1] ?? $parts[0]);
    }

    private function cleanModuleName(string $name): string
    {
        $name = preg_replace('/^[\x{1F300}-\x{1F9FF}\x{2600}-\x{27BF}\x{2700}-\x{27BF}\x{FE00}-\x{FE0F}\x{1F000}-\x{1FFFF}\x{200D}\x{20E3}\x{E0020}-\x{E007F}✅🪴👩🏻‍🏫]+\s*/u', '', $name);

        $name = preg_replace('/\s*-\s*Übergabe\s*$/u', '', $name);
        $name = preg_replace('/\s*\(bis\s+[^)]+\)\s*$/u', '', $name);
        $name = preg_replace('/\s*\(\d+\/\d+\)\s*$/', '', $name);
        $name = preg_replace('/\s*\([^)]+\)\s*$/', '', $name);

        return trim($name);
    }

    private function mapFormatToType(array $row): string
    {
        $format = trim($row['Format'] ?? '');

        if (! empty($format) && isset(self::FORMAT_TYPE_MAP[$format])) {
            return self::FORMAT_TYPE_MAP[$format];
        }

        return 'workshop';
    }

    private function previewImport(array $grouped): void
    {
        foreach ($grouped as $pathName => $levels) {
            $this->components->info("Karrierepfad: {$pathName}");

            $levelOrder = $this->sortLevels($pathName, array_keys($levels));

            foreach ($levelOrder as $levelName) {
                $modules = $levels[$levelName] ?? [];
                $this->components->twoColumnDetail(
                    "  {$levelName}",
                    count($modules) . ' Module'
                );

                foreach ($modules as $row) {
                    $cleanName = $this->cleanModuleName(trim($row['Name'] ?? ''));
                    $type = $this->mapFormatToType($row);
                    $skill = trim($row['Skill-Bereich'] ?? '') ?: '-';
                    $desc = $this->extractDescription(trim($row['Notes'] ?? ''));
                    $this->line("    - {$cleanName} [{$type}] ({$skill})");
                    if ($desc) {
                        $preview = mb_substr(str_replace("\n", ' ', $desc), 0, 100);
                        $this->line("      📝 {$preview}" . (mb_strlen($desc) > 100 ? '...' : ''));
                    } else {
                        $this->line("      (keine Beschreibung extrahiert)");
                    }
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
            }

            $levelOrder = $this->sortLevels($pathName, array_keys($levels));

            foreach ($levelOrder as $index => $levelName) {
                $levelNumber = $this->getLevelNumber($pathName, $levelName);

                $level = CareerLevel::firstOrCreate(
                    ['career_path_id' => $path->id, 'level_number' => $levelNumber],
                    ['title' => $levelName]
                );

                if ($level->wasRecentlyCreated) {
                    $this->stats['levels_created']++;
                    $this->line("  Stufe erstellt: {$levelName} (#{$levelNumber})");
                }

                $modules = $levels[$levelName] ?? [];
                $sortOrder = 0;

                $updateDescriptions = $this->option('update-descriptions');

                foreach ($modules as $row) {
                    $sortOrder++;
                    $cleanName = $this->cleanModuleName(trim($row['Name'] ?? ''));
                    $type = $this->mapFormatToType($row);
                    $skillName = trim($row['Skill-Bereich'] ?? '') ?: null;
                    $skillCategoryId = null;
                    $description = $this->extractDescription(trim($row['Notes'] ?? ''));

                    if ($skillName) {
                        $skillCategoryId = SkillCategory::firstOrCreate(['name' => $skillName])->id;
                    }

                    $module = Module::firstOrCreate(
                        ['career_level_id' => $level->id, 'title' => $cleanName],
                        [
                            'description' => $description,
                            'type' => $type,
                            'skill_category_id' => $skillCategoryId,
                            'is_mandatory' => true,
                            'sort_order' => $sortOrder,
                        ]
                    );

                    if ($module->wasRecentlyCreated) {
                        $this->stats['modules_created']++;
                        $this->line("    + {$cleanName}");
                        if ($description) {
                            $this->line("      📝 " . mb_substr($description, 0, 80) . (mb_strlen($description) > 80 ? '...' : ''));
                        }
                    } elseif ($updateDescriptions && $description) {
                        $module->update(['description' => $description]);
                        $this->stats['modules_updated']++;
                        $this->line("    ~ {$cleanName} (Beschreibung aktualisiert)");
                        $this->line("      📝 " . mb_substr($description, 0, 80) . (mb_strlen($description) > 80 ? '...' : ''));
                    } else {
                        $this->stats['modules_skipped']++;
                        $this->line("    ~ {$cleanName} (übersprungen)");
                    }
                }
            }
        }
    }

    private function sortLevels(string $pathName, array $levelNames): array
    {
        $order = match ($pathName) {
            'TD Allgemein' => ['Übergreifend', 'Onboarding', 'Junior', 'Professional'],
            default => ['Specialist', 'Senior', 'Experte'],
        };

        $sorted = [];
        foreach ($order as $name) {
            if (in_array($name, $levelNames, true)) {
                $sorted[] = $name;
            }
        }

        foreach ($levelNames as $name) {
            if (! in_array($name, $sorted, true)) {
                $sorted[] = $name;
            }
        }

        return $sorted;
    }

    /**
     * Extract a clean module description from Asana task Notes.
     *
     * Prioritizes structured sections like "Worum geht's?", "Ziel:", "Themen/Struktur:",
     * "Ziel der Schulung:", etc. Falls back to the first meaningful paragraph
     * after stripping boilerplate intros and internal process text.
     */
    private function extractDescription(string $notes): ?string
    {
        if (empty($notes)) {
            return null;
        }

        $parts = [];

        $sectionPatterns = [
            '/Worum geht.s\??\s*\n(.+?)(?=\n(?:Ziel|Methode|Inhalte|Was brauche ich|Next Steps|Fragen\?)|$)/su',
            '/(?:🎯\s*)?Ziel(?:\s+der\s+Schulung)?:\s*\n(.+?)(?=\n(?:💡|ℹ️|Methode|Inhalte|Was brauche ich|Next Steps|Mögliche|✨|✍|🧠|Fragen\?)|$)/su',
            '/Themen\/Struktur:\s*\n(.+?)(?=\n(?:Gesamt|https?:|Next Steps|$))/su',
            '/(?:💡|ℹ️)\s*Mögliche Inhalte[^:]*:\s*\n(.+?)(?=\n(?:✨|Next Steps|Die Inhalte sollen|Accountable|🧭|Wir freuen)|$)/su',
            '/Inhalte:\s*\n(.+?)(?=\n(?:Ziel|Methode|Was brauche ich|Next Steps|Fragen\?)|$)/su',
            '/✍🏻\s*(?:Deine Aufgabe|Was ist zu tun)[^:]*:\s*\n(.+?)(?=\n(?:🧠|💡|ℹ️|✨|Next Steps|Mögliche|Accountable)|$)/su',
        ];

        foreach ($sectionPatterns as $pattern) {
            if (preg_match($pattern, $notes, $match)) {
                $extracted = $this->cleanExtractedText($match[1]);
                if (mb_strlen($extracted) >= 20) {
                    $parts[] = $extracted;
                }
            }
        }

        if (! empty($parts)) {
            $description = implode("\n", array_unique($parts));
            return mb_substr($description, 0, 2000);
        }

        // Fallback: find the first meaningful paragraph after common intro patterns
        $lines = preg_split('/\n{2,}/', $notes);
        foreach ($lines as $block) {
            $block = trim($block);
            if (mb_strlen($block) < 30) {
                continue;
            }
            if (preg_match('/^(Hey |Liebe[r ]|Hi |Hallo )/u', $block)) {
                continue;
            }
            if (preg_match('/^(wir entwickeln|Wie läuft|Let.s go|Next Steps|Fragen\?|Was brauche|https?:)/iu', $block)) {
                continue;
            }
            if (preg_match('/^(Accountable|Sparrings|Prio:|neue DEADLINE)/iu', $block)) {
                continue;
            }

            $cleaned = $this->cleanExtractedText($block);
            if (mb_strlen($cleaned) >= 30) {
                return mb_substr($cleaned, 0, 2000);
            }
        }

        return null;
    }

    private function cleanExtractedText(string $text): string
    {
        $text = preg_replace('/https?:\/\/\S+/u', '', $text);
        $text = preg_replace('/^\s*[\x{1F300}-\x{1FFFF}]+\s*/mu', '', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        $lines = explode("\n", $text);
        $cleaned = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                $cleaned[] = '';
                continue;
            }
            if (preg_match('/^(Accountable|Sparrings-Partner|Prio:|🧭|🤝|📌)/u', $line)) {
                continue;
            }
            $cleaned[] = $line;
        }

        return trim(implode("\n", $cleaned));
    }

    private function getLevelNumber(string $pathName, string $levelName): int
    {
        if ($pathName === 'TD Allgemein') {
            return match ($levelName) {
                'Übergreifend', 'Onboarding' => 1,
                'Junior' => 2,
                'Professional' => 3,
                default => 99,
            };
        }

        return match ($levelName) {
            'Specialist' => 1,
            'Senior' => 2,
            'Experte' => 3,
            default => 99,
        };
    }
}
