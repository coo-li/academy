<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModuleAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $users = User::active()
            ->with(['assignedModules', 'careerLevel.careerPath', 'careerLevels.careerPath'])
            ->orderBy('name')
            ->get();

        $modules = Module::with('careerLevel.careerPath')
            ->orderBy('sort_order')
            ->get();

        $globalModules = $modules->where('career_level_id', null);
        $careerModules = $modules->where('career_level_id', '!=', null)->groupBy(fn ($m) => $m->careerLevel->careerPath->name . ' – ' . $m->careerLevel->title);

        return view('manage.assignments', compact('users', 'modules', 'globalModules', 'careerModules'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'module_id' => 'required|exists:modules,id',
        ]);

        $assignedBy = Auth::id();
        $now = now();
        $count = 0;

        foreach ($validated['user_ids'] as $userId) {
            $inserted = DB::table('module_assignments')->insertOrIgnore([
                'user_id' => $userId,
                'module_id' => $validated['module_id'],
                'assigned_by' => $assignedBy,
                'assigned_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $count += $inserted;
        }

        $module = Module::find($validated['module_id']);

        return back()->with('success', "Modul \"{$module->title}\" wurde {$count} Mitarbeitenden zugewiesen.");
    }

    public function destroy(Request $request, User $user, Module $module)
    {
        $user->assignedModules()->detach($module->id);

        return back()->with('success', "Zuordnung von \"{$module->title}\" für {$user->name} entfernt.");
    }
}
