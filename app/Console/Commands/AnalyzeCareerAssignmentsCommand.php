<?php

namespace App\Console\Commands;

use App\Models\PersonioPositionMapping;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyzeCareerAssignmentsCommand extends Command
{
    protected $signature = 'academy:analyze-assignments';

    protected $description = 'Analysiere Karrierestufen-Zuweisungen und identifiziere manuelle/überschüssige Zuweisungen';

    public function handle(): int
    {
        $this->info('🔍 Analysiere Karrierestufen-Zuweisungen...');
        $this->newLine();

        $usersWithAssignments = User::has('careerLevels')
            ->with(['careerLevels.careerPath'])
            ->get();

        $this->info("Gefundene User mit Karrierestufen-Zuweisungen: {$usersWithAssignments->count()}");
        $this->newLine();

        $noPersonioId = collect();
        $overheadWithAssignments = collect();
        $excessAssignments = collect();
        $matchingAssignments = collect();

        foreach ($usersWithAssignments as $user) {
            $assignedLevelIds = $user->careerLevels->pluck('id')->toArray();

            if (! $user->personio_id) {
                $noPersonioId->push([
                    'user' => $user,
                    'assignments' => $user->careerLevels,
                ]);
                continue;
            }

            $levelRaw = Str::lower(trim($user->personio_level_raw ?? ''));
            if (in_array($levelRaw, ['overhead', 'head of'])) {
                $overheadWithAssignments->push([
                    'user' => $user,
                    'level_raw' => $user->personio_level_raw,
                    'assignments' => $user->careerLevels,
                ]);
                continue;
            }

            $expectedLevelIds = $this->getExpectedCareerLevelIds($user);

            $excess = array_diff($assignedLevelIds, $expectedLevelIds);
            $missing = array_diff($expectedLevelIds, $assignedLevelIds);

            if (! empty($excess)) {
                $excessLevels = $user->careerLevels->filter(fn ($l) => in_array($l->id, $excess));
                $excessAssignments->push([
                    'user' => $user,
                    'excess_levels' => $excessLevels,
                    'expected_count' => count($expectedLevelIds),
                    'actual_count' => count($assignedLevelIds),
                ]);
            } else {
                $matchingAssignments->push($user);
            }
        }

        $this->displaySection(
            '❌ User ohne Personio-ID (rein manuell)',
            $noPersonioId,
            fn ($item) => [
                $item['user']->id,
                $item['user']->name,
                $item['user']->email,
                $item['assignments']->map(fn ($l) => $l->careerPath?->name . ' - ' . $l->title)->implode(', '),
            ],
            ['ID', 'Name', 'E-Mail', 'Zugewiesene Karrierestufen']
        );

        $this->displaySection(
            '⚠️ Overhead/Head-of mit Zuweisungen (sollten keine haben)',
            $overheadWithAssignments,
            fn ($item) => [
                $item['user']->id,
                $item['user']->name,
                $item['user']->personio_level_raw,
                $item['assignments']->map(fn ($l) => $l->careerPath?->name . ' - ' . $l->title)->implode(', '),
            ],
            ['ID', 'Name', 'Personio-Level', 'Zugewiesene Karrierestufen']
        );

        $this->displaySection(
            '📦 User mit überschüssigen Zuweisungen (mehr als Personio sagt)',
            $excessAssignments,
            fn ($item) => [
                $item['user']->id,
                $item['user']->name,
                $item['user']->personio_path_raw ?? '–',
                $item['user']->personio_level_raw ?? '–',
                $item['expected_count'],
                $item['actual_count'],
                $item['excess_levels']->map(fn ($l) => $l->careerPath?->name . ' - ' . $l->title)->implode(', '),
            ],
            ['ID', 'Name', 'Personio-Pfad', 'Personio-Level', 'Erwartet', 'Tatsächlich', 'Überschüssige Zuweisungen']
        );

        $moduleAssignments = DB::table('module_assignments')
            ->join('users', 'module_assignments.user_id', '=', 'users.id')
            ->join('modules', 'module_assignments.module_id', '=', 'modules.id')
            ->leftJoin('users as assigners', 'module_assignments.assigned_by', '=', 'assigners.id')
            ->select('users.id', 'users.name', 'modules.title as module_title', 'assigners.name as assigned_by_name')
            ->get();

        $this->newLine();
        $this->line('📚 Direkte Modul-Zuweisungen (außerhalb Karrierepfad)');
        if ($moduleAssignments->isEmpty()) {
            $this->line('  (keine)');
        } else {
            $this->table(
                ['User-ID', 'Name', 'Modul', 'Zugewiesen von'],
                $moduleAssignments->map(fn ($a) => [$a->id, $a->name, $a->module_title, $a->assigned_by_name ?? '–'])->toArray()
            );
        }

        $this->newLine();
        $this->info('📊 Zusammenfassung:');
        $this->table(
            ['Kategorie', 'Anzahl'],
            [
                ['User ohne Personio-ID', $noPersonioId->count()],
                ['Overhead/Head-of mit Zuweisungen', $overheadWithAssignments->count()],
                ['User mit überschüssigen Karrierestufen', $excessAssignments->count()],
                ['User mit korrekten Karrierestufen', $matchingAssignments->count()],
                ['Direkte Modul-Zuweisungen', $moduleAssignments->count()],
            ]
        );

        $manualCount = $noPersonioId->count() + $overheadWithAssignments->count() + $excessAssignments->count() + $moduleAssignments->count();
        if ($manualCount > 0) {
            $this->newLine();
            $this->warn("⚠️  {$manualCount} manuelle Zuweisungen gefunden.");
            $this->line('Führe `php artisan academy:reset-career-assignments` aus, um alle Zuweisungen zurückzusetzen.');
        } else {
            $this->newLine();
            $this->info('✅ Alle Zuweisungen entsprechen den Personio-Daten.');
        }

        return self::SUCCESS;
    }

    protected function getExpectedCareerLevelIds(User $user): array
    {
        if (! $user->personio_position) {
            return [];
        }

        $paths = $user->personio_path_raw
            ? array_map('trim', explode(',', $user->personio_path_raw))
            : [null];

        $expectedIds = [];

        foreach ($paths as $path) {
            $mapping = PersonioPositionMapping::where('personio_position', $user->personio_position)
                ->where('personio_level_raw', $user->personio_level_raw)
                ->where('personio_path_raw', $path)
                ->first();

            if ($mapping && $mapping->isMapped()) {
                $expectedIds[] = $mapping->career_level_id;
            }
        }

        return array_unique($expectedIds);
    }

    protected function displaySection(string $title, Collection $items, callable $rowMapper, array $headers): void
    {
        $this->newLine();
        $this->line($title);

        if ($items->isEmpty()) {
            $this->line('  (keine)');
            return;
        }

        $this->table($headers, $items->map($rowMapper)->toArray());
    }
}
