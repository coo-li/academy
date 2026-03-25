<?php

namespace App\Http\Controllers;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\PersonioPositionMapping;
use App\Models\PersonioSyncLog;
use App\Models\User;
use App\Services\PersonioService;
use Illuminate\Http\Request;

class AdminMatrixController extends Controller
{
    public function index()
    {
        $mappings = PersonioPositionMapping::with(['careerPath', 'careerLevel.modules'])
            ->orderBy('personio_position')
            ->orderBy('personio_level_raw')
            ->orderBy('personio_path_raw')
            ->get();

        $careerPaths = CareerPath::with('levels')->orderBy('name')->get();

        $lastSync = PersonioService::getLastSync();

        $userCounts = User::whereNotNull('personio_position')
            ->selectRaw('personio_position, personio_level_raw, personio_path_raw, count(*) as cnt')
            ->groupBy('personio_position', 'personio_level_raw', 'personio_path_raw')
            ->get()
            ->keyBy(fn ($row) => $row->personio_position . '|' . $row->personio_level_raw . '|' . $row->personio_path_raw);

        $structureGaps = [];
        foreach ($mappings as $mapping) {
            if ($mapping->isMapped() && $mapping->careerLevel->modules->isEmpty()) {
                $structureGaps[$mapping->id] = true;
            }
        }

        $stats = [
            'total_combinations' => $mappings->count(),
            'mapped' => $mappings->filter->isMapped()->count(),
            'unmapped' => $mappings->reject->isMapped()->reject(fn ($m) => in_array(strtolower($m->personio_level_raw ?? ''), ['overhead', 'head of']))->count(),
            'auto_matched' => $mappings->where('is_auto_matched', true)->count(),
            'users_without_path' => PersonioService::getUsersWithoutCareerPath(),
            'total_personio_users' => User::whereNotNull('personio_id')->count(),
            'structure_gaps' => count($structureGaps),
        ];

        return view('admin.matrix.index', compact(
            'mappings',
            'careerPaths',
            'lastSync',
            'stats',
            'userCounts',
            'structureGaps'
        ));
    }

    public function updateMapping(Request $request, PersonioPositionMapping $mapping)
    {
        $request->validate([
            'career_level_id' => ['nullable', 'exists:career_levels,id'],
        ]);

        $careerLevel = $request->career_level_id
            ? CareerLevel::find($request->career_level_id)
            : null;

        $mapping->update([
            'career_path_id' => $careerLevel?->career_path_id,
            'career_level_id' => $careerLevel?->id,
            'is_auto_matched' => false,
        ]);

        if ($careerLevel) {
            $users = User::where(function ($q) use ($mapping) {
                    $q->where('personio_path_raw', $mapping->personio_path_raw)
                      ->orWhere('personio_path_raw', 'LIKE', '%' . $mapping->personio_path_raw . '%');
                })
                ->where('personio_position', $mapping->personio_position)
                ->where('personio_level_raw', $mapping->personio_level_raw)
                ->get();

            $affected = 0;
            foreach ($users as $user) {
                if (! $user->careerLevels->contains('id', $careerLevel->id)) {
                    $user->addCareerLevel($careerLevel);
                    $affected++;
                }
            }

            $label = $careerLevel->careerPath?->name . ' – ' . $careerLevel->title;

            return back()->with('success', "Mapping aktualisiert: \"{$mapping->personio_position}\" ({$mapping->personio_level_raw}) → {$label}. {$affected} User zugewiesen.");
        }

        return back()->with('success', "Mapping für \"{$mapping->personio_position}\" ({$mapping->personio_level_raw}) entfernt.");
    }

    public function autoMap(PersonioService $personio)
    {
        $result = $personio->runAutoMapAndAssign();

        $msg = "{$result['mappings_matched']} Mappings automatisch zugeordnet, {$result['users_assigned']} User Karrierepfade zugewiesen.";

        return back()->with('success', $msg);
    }

    public function sync(PersonioService $personio)
    {
        if (! $personio->isConfigured()) {
            return back()->with('error', 'Personio API ist nicht konfiguriert. Bitte Client ID und Secret in .env eintragen.');
        }

        $log = $personio->syncEmployees();

        if ($log->isSuccess()) {
            $details = $log->details ?? [];
            $autoMapped = $details['mappings_auto_matched'] ?? 0;
            $assigned = $details['users_career_assigned'] ?? 0;

            $msg = "Sync erfolgreich: {$log->employees_fetched} Mitarbeiter abgerufen, {$log->users_created} neu, {$log->users_updated} aktualisiert.";
            if ($autoMapped > 0 || $assigned > 0) {
                $msg .= " Auto-Mapping: {$autoMapped} Zuordnungen, {$assigned} User zugewiesen.";
            }

            return back()->with('success', $msg);
        }

        return back()->with('error', 'Sync fehlgeschlagen: ' . ($log->error_message ?? 'Unbekannter Fehler'));
    }
}
