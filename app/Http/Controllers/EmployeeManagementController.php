<?php

namespace App\Http\Controllers;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeManagementController extends Controller
{
    public function index(Request $request)
    {
        $manager = Auth::user();

        $query = $manager->managedEmployees()
            ->with(['team', 'careerLevel.careerPath', 'assignedModules', 'disabledCareerModules']);

        $employees = $query->orderBy('name')->get();
        $teams = $manager->isAdmin()
            ? \App\Models\Team::orderBy('name')->get()
            : $manager->managedTeams()->orderBy('name')->get();

        $employeesJson = $employees->map(function ($e) {
            return [
                'id' => $e->id,
                'name' => $e->name,
                'initials' => mb_strtoupper(mb_substr($e->name, 0, 2)),
                'team' => $e->team?->name,
                'level' => $e->careerLevel?->title,
                'path' => $e->careerLevel?->careerPath?->name,
                'moduleCount' => $e->assignedModules->count() + ($e->careerLevel ? $e->careerLevel->modules()->count() - $e->disabledCareerModules->count() : 0),
            ];
        })->values();

        return view('manage.employees.index', compact('employees', 'teams', 'employeesJson'));
    }

    public function show(User $user)
    {
        $this->authorizeEmployee($user);

        $user->load([
            'team',
            'careerLevel.careerPath',
            'assignedModules.careerLevel.careerPath',
            'assignedModules.skillCategory',
            'enrollments.module',
            'disabledCareerModules',
        ]);

        $careerModules = $user->careerLevel
            ? $user->careerLevel->modules()->with(['skillCategory', 'careerLevel.careerPath'])->orderBy('sort_order')->get()
            : collect();

        $disabledModuleIds = $user->disabledCareerModules->pluck('id')->toArray();
        $activeCareerModules = $careerModules->reject(fn ($m) => in_array($m->id, $disabledModuleIds));

        $allModules = $activeCareerModules->merge($user->assignedModules)->unique('id')->sortBy('sort_order')->values();
        $assignedModuleIds = $user->assignedModules->pluck('id')->toArray();

        $enrollmentMap = $user->enrollments
            ->groupBy('module_id')
            ->map(fn ($enrollments) => $enrollments->sortByDesc('created_at')->first());

        $suggestions = $this->getSuggestions($user);

        $careerPaths = CareerPath::with('levels')->orderBy('name')->get();
        $availableModules = Module::with('careerLevel.careerPath')
            ->orderBy('sort_order')
            ->get();

        $availableModulesJson = $availableModules->map(fn ($m) => [
            'id' => $m->id,
            'title' => $m->title,
            'career_level_id' => $m->career_level_id,
            'path_id' => $m->careerLevel?->careerPath?->id,
            'path_name' => $m->careerLevel?->careerPath?->name,
            'level_title' => $m->careerLevel?->title,
        ]);

        return view('manage.employees.show', compact(
            'user',
            'allModules',
            'assignedModuleIds',
            'careerModules',
            'disabledModuleIds',
            'enrollmentMap',
            'suggestions',
            'careerPaths',
            'availableModules',
            'availableModulesJson',
        ));
    }

    public function assignModule(Request $request, User $user)
    {
        $this->authorizeEmployee($user);

        $validated = $request->validate([
            'module_id' => 'required|exists:modules,id',
        ]);

        DB::table('module_assignments')->insertOrIgnore([
            'user_id' => $user->id,
            'module_id' => $validated['module_id'],
            'assigned_by' => Auth::id(),
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $module = Module::find($validated['module_id']);
        $msg = "Modul \"{$module->title}\" wurde {$user->name} zugewiesen.";

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    public function removeModule(Request $request, User $user, Module $module)
    {
        $this->authorizeEmployee($user);

        $user->assignedModules()->detach($module->id);
        $msg = "Modul \"{$module->title}\" wurde von {$user->name} entfernt.";

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    public function assignCareerLevel(Request $request, User $user)
    {
        $this->authorizeEmployee($user);

        $validated = $request->validate([
            'career_level_id' => 'nullable|exists:career_levels,id',
        ]);

        $user->update([
            'career_level_id' => $validated['career_level_id'],
        ]);

        $levelTitle = $user->fresh('careerLevel.careerPath')->careerLevel
            ? $user->careerLevel->careerPath->name.' – '.$user->careerLevel->title
            : 'Keiner';

        $msg = "Karrierepfad für {$user->name} aktualisiert: {$levelTitle}";

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    public function removeCareerPath(Request $request, User $user)
    {
        $this->authorizeEmployee($user);

        $user->update(['career_level_id' => null]);
        $msg = "Karrierepfad für {$user->name} entfernt.";

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    public function disableCareerModule(Request $request, User $user, Module $module)
    {
        $this->authorizeEmployee($user);

        DB::table('disabled_career_modules')->insertOrIgnore([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'disabled_by' => Auth::id(),
            'disabled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $msg = "Modul \"{$module->title}\" wurde für {$user->name} deaktiviert.";

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    public function enableCareerModule(Request $request, User $user, Module $module)
    {
        $this->authorizeEmployee($user);

        $user->disabledCareerModules()->detach($module->id);
        $msg = "Modul \"{$module->title}\" wurde für {$user->name} wieder aktiviert.";

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    protected function getSuggestions(User $user): array
    {
        $suggestions = [];

        if (! $user->careerLevel) {
            $suggestions[] = [
                'type' => 'career_path',
                'message' => 'Kein Karrierepfad zugewiesen. Bitte einen Karrierepfad und eine Stufe zuweisen.',
            ];

            return $suggestions;
        }

        $careerModules = $user->careerLevel->modules;
        $assignedIds = $user->assignedModules->pluck('id')->toArray();
        $disabledIds = $user->disabledCareerModules->pluck('id')->toArray();

        $completedModuleIds = $user->enrollments
            ->where('status', 'completed')
            ->pluck('module_id')
            ->toArray();

        $missingModules = $careerModules->filter(function ($module) use ($completedModuleIds, $disabledIds) {
            return ! in_array($module->id, $completedModuleIds) && ! in_array($module->id, $disabledIds);
        });

        if ($missingModules->isNotEmpty()) {
            $suggestions[] = [
                'type' => 'missing_modules',
                'message' => $missingModules->count().' Module der aktuellen Karrierestufe noch nicht abgeschlossen.',
                'modules' => $missingModules->pluck('title', 'id')->toArray(),
            ];
        }

        if ($missingModules->isEmpty()) {
            $nextLevel = CareerLevel::where('career_path_id', $user->careerLevel->career_path_id)
                ->where('level_number', '>', $user->careerLevel->level_number)
                ->orderBy('level_number')
                ->first();

            if ($nextLevel) {
                $suggestions[] = [
                    'type' => 'next_level',
                    'message' => "Alle Module abgeschlossen! Nächste Stufe: {$nextLevel->title}",
                    'career_level_id' => $nextLevel->id,
                ];
            }
        }

        return $suggestions;
    }

    protected function authorizeEmployee(User $user): void
    {
        $manager = Auth::user();

        if ($manager->isAdmin()) {
            return;
        }

        $teamIds = $manager->managedTeams()->pluck('teams.id')->toArray();

        if (! in_array($user->team_id, $teamIds)) {
            abort(403, 'Kein Zugriff auf diesen Mitarbeiter.');
        }
    }
}
