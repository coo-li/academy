<?php

namespace App\Http\Controllers;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Milestone;
use App\Models\Module;
use App\Models\ModuleInterest;
use App\Models\User;
use App\Notifications\ModuleAssigned;
use App\Services\ModuleInterestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeManagementController extends Controller
{
    public function index(Request $request)
    {
        $manager = Auth::user();

        $scope = $request->query('scope', 'mine');
        $teamFilter = $request->query('team');

        if ($scope === 'all' && ! $manager->isAdmin()) {
            abort(403);
        }

        if (! $manager->isPeopleManagerOrHeadOf()) {
            $scope = 'all';
        }

        $query = $scope === 'all'
            ? $manager->managedEmployees()
            : $manager->teamEmployees();

        $query->with(['team', 'careerLevel.careerPath', 'careerLevels.careerPath', 'assignedModules', 'disabledCareerModules', 'roles']);

        if ($teamFilter && $teamFilter !== 'head_ofs' && $teamFilter > 0) {
            $query->where('team_id', $teamFilter);
        }

        $employees = $query->orderBy('name')->get();

        if ($manager->isCLevel() && $scope !== 'all') {
            $headOfQuery = $manager->headOfReports()
                ->with(['team', 'careerLevel.careerPath', 'careerLevels.careerPath', 'assignedModules', 'disabledCareerModules', 'roles']);

            if ($teamFilter === 'head_ofs') {
                $employees = $headOfQuery->orderBy('name')->get();
            } elseif (! $teamFilter) {
                $headOfs = $headOfQuery->orderBy('name')->get();
                $employees = $employees->merge($headOfs)->unique('id')->sortBy('name')->values();
            }
        }

        $teams = $scope === 'all'
            ? \App\Models\Team::orderBy('name')->get()
            : $manager->managedTeams()->orderBy('name')->get();

        if ($manager->isCLevel() && $scope !== 'all') {
            $headOfCount = $manager->headOfReports()->count();
            if ($headOfCount > 0) {
                $virtualTeam = new \App\Models\Team(['name' => 'Head-Ofs']);
                $virtualTeam->setAttribute('id', 'head_ofs');
                $teams->prepend($virtualTeam);
            }
        }

        $employeesJson = $employees->map(function ($e) {
            $careerModuleCount = $e->careerLevels->sum(fn ($l) => $l->modules()->count());

            return [
                'id' => $e->id,
                'name' => $e->name,
                'email' => $e->email,
                'initials' => $e->initials,
                'team' => $e->team?->name,
                'teamId' => $e->team_id,
                'level' => $e->careerLevel?->title,
                'path' => $e->careerLevel?->careerPath?->name,
                'paths' => $e->careerLevels->map(fn ($l) => $l->careerPath->name . ' – ' . $l->title)->values()->all(),
                'moduleCount' => $e->assignedModules->count() + $careerModuleCount - $e->disabledCareerModules->count(),
                'isHeadOf' => $e->hasHeadOfRole(),
            ];
        })->values();

        return view('manage.employees.index', compact('employees', 'teams', 'employeesJson', 'scope', 'teamFilter'));
    }

    public function show(User $user)
    {
        $this->authorizeEmployee($user);

        $user->load([
            'team',
            'careerLevel.careerPath',
            'careerLevels.careerPath',
            'assignedModules.careerLevel.careerPath',
            'assignedModules.skillCategory',
            'enrollments.module',
            'enrollments.trainingSession',
            'disabledCareerModules',
            'disabledMilestones',
        ]);

        $careerModules = $user->careerLevels
            ->flatMap(fn ($level) => $level->modules()->with(['skillCategory', 'careerLevel.careerPath'])->orderBy('sort_order')->get())
            ->unique('id');

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

        $pendingInterests = $user->moduleInterests()
            ->with('module.careerLevel.careerPath')
            ->whereNull('noted_at')
            ->orderByDesc('created_at')
            ->get();

        $disabledMilestoneIds = $user->disabledMilestones->pluck('id')->toArray();
        $allMilestones = Milestone::forUser($user)
            ->orderBy('category')->orderBy('sort_order')
            ->get();
        $activeMilestones = $allMilestones->reject(fn ($m) => in_array($m->id, $disabledMilestoneIds));
        $disabledMilestonesList = $allMilestones->filter(fn ($m) => in_array($m->id, $disabledMilestoneIds));

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
            'pendingInterests',
            'activeMilestones',
            'disabledMilestoneIds',
            'disabledMilestonesList',
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
        $user->notify(new ModuleAssigned($module, Auth::user()));

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

        if ($validated['career_level_id']) {
            $level = CareerLevel::find($validated['career_level_id']);
            $user->addCareerLevel($level);

            $label = $level->careerPath->name . ' – ' . $level->title;
            $msg = "Karrierepfad für {$user->name} hinzugefügt: {$label}";
        } else {
            $user->careerLevels()->detach();
            $user->syncPrimaryCareerLevel();
            $msg = "Alle Karrierepfade für {$user->name} entfernt.";
        }

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    public function removeCareerPath(Request $request, User $user)
    {
        $this->authorizeEmployee($user);

        $validated = $request->validate([
            'career_level_id' => 'nullable|exists:career_levels,id',
        ]);

        if ($validated['career_level_id']) {
            $level = CareerLevel::find($validated['career_level_id']);
            $user->removeCareerLevel($level);
            $msg = "Karrierepfad \"{$level->careerPath->name} – {$level->title}\" für {$user->name} entfernt.";
        } else {
            $user->careerLevels()->detach();
            $user->syncPrimaryCareerLevel();
            $msg = "Alle Karrierepfade für {$user->name} entfernt.";
        }

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

    public function disableMilestone(Request $request, User $user, Milestone $milestone)
    {
        $this->authorizeEmployee($user);

        DB::table('disabled_milestones')->insertOrIgnore([
            'user_id' => $user->id,
            'milestone_id' => $milestone->id,
            'disabled_by' => Auth::id(),
            'disabled_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $msg = "Milestone \"{$milestone->title}\" wurde für {$user->name} deaktiviert.";

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    public function enableMilestone(Request $request, User $user, Milestone $milestone)
    {
        $this->authorizeEmployee($user);

        $user->disabledMilestones()->detach($milestone->id);
        $msg = "Milestone \"{$milestone->title}\" wurde für {$user->name} wieder aktiviert.";

        return redirect()->route('manage.employees.show', $user)->with('success', $msg);
    }

    public function noteInterest(Request $request, User $user, ModuleInterest $interest)
    {
        $this->authorizeEmployee($user);

        if ($interest->user_id !== $user->id) {
            abort(403);
        }

        app(ModuleInterestService::class)->markAsNoted($interest, Auth::user());

        return redirect()->route('manage.employees.show', $user)
            ->with('success', "Interesse an \"{$interest->module->title}\" wurde zur Kenntnis genommen.");
    }

    public function assignFromInterest(Request $request, User $user, ModuleInterest $interest)
    {
        $this->authorizeEmployee($user);

        if ($interest->user_id !== $user->id) {
            abort(403);
        }

        DB::table('module_assignments')->insertOrIgnore([
            'user_id' => $user->id,
            'module_id' => $interest->module_id,
            'assigned_by' => Auth::id(),
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(ModuleInterestService::class)->markAsNoted($interest, Auth::user());

        $module = $interest->module;
        $user->notify(new ModuleAssigned($module, Auth::user()));

        return redirect()->route('manage.employees.show', $user)
            ->with('success', "Modul \"{$module->title}\" wurde {$user->name} zugewiesen.");
    }

    protected function getSuggestions(User $user): array
    {
        $suggestions = [];

        if ($user->careerLevels->isEmpty()) {
            $suggestions[] = [
                'type' => 'career_path',
                'message' => 'Kein Karrierepfad zugewiesen. Bitte einen Karrierepfad und eine Stufe zuweisen.',
            ];

            return $suggestions;
        }

        $disabledIds = $user->disabledCareerModules->pluck('id')->toArray();
        $completedModuleIds = $user->enrollments
            ->where('status', 'completed')
            ->pluck('module_id')
            ->toArray();

        foreach ($user->careerLevels as $level) {
            $careerModules = $level->modules;
            $pathLabel = $level->careerPath->name . ' – ' . $level->title;

            $missingModules = $careerModules->filter(function ($module) use ($completedModuleIds, $disabledIds) {
                return ! in_array($module->id, $completedModuleIds) && ! in_array($module->id, $disabledIds);
            });

            if ($missingModules->isNotEmpty()) {
                $suggestions[] = [
                    'type' => 'missing_modules',
                    'message' => $missingModules->count() . " Module in {$pathLabel} noch nicht abgeschlossen.",
                    'modules' => $missingModules->pluck('title', 'id')->toArray(),
                ];
            }

            if ($missingModules->isEmpty()) {
                $nextLevel = CareerLevel::where('career_path_id', $level->career_path_id)
                    ->where('level_number', '>', $level->level_number)
                    ->orderBy('level_number')
                    ->first();

                if ($nextLevel) {
                    $suggestions[] = [
                        'type' => 'next_level',
                        'message' => "Alle Module in {$pathLabel} abgeschlossen! Nächste Stufe: {$nextLevel->title}",
                        'career_level_id' => $nextLevel->id,
                    ];
                }
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

        if ($manager->isCLevel() && $user->hasHeadOfRole()) {
            return;
        }

        $teamIds = $manager->managedTeams()->pluck('teams.id')->toArray();

        if (! in_array($user->team_id, $teamIds)) {
            abort(403, 'Kein Zugriff auf diesen Mitarbeiter.');
        }
    }
}
