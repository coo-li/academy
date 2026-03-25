<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModuleRequest;
use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Method;
use App\Models\Module;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    public function index(Request $request)
    {
        $methodFilter = null;
        $filteredModules = null;

        if ($request->filled('method_id')) {
            $methodFilter = Method::find($request->method_id);
            if ($methodFilter) {
                $filteredModules = Module::where('method_id', $methodFilter->id)
                    ->with(['careerLevel.careerPath', 'skillCategory', 'method'])
                    ->orderBy('sort_order')
                    ->get();
            }
        }

        $paths = CareerPath::withCount(['levels', 'modules'])
            ->orderBy('name')
            ->get();

        $skillGroups = SkillCategory::withCount('modules')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $unassignedModules = Module::whereNull('career_level_id')
            ->whereNull('skill_category_id')
            ->with(['method'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.modules.index', compact('paths', 'skillGroups', 'unassignedModules', 'methodFilter', 'filteredModules'));
    }

    public function showPath(CareerPath $path)
    {
        $path->load(['levels.modules' => fn ($q) => $q->with(['skillCategory', 'accountableUser', 'method', 'quiz'])->orderBy('sort_order')]);

        return view('admin.modules.show-path', compact('path'));
    }

    public function create()
    {
        $levels = CareerLevel::with('careerPath')->get()
            ->groupBy('careerPath.name');
        $skillCategories = SkillCategory::orderBy('name')->get();
        $methods = Method::orderBy('name')->get();
        $teachers = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['admin', 'people_manager', 'head_of', 'trainer']))
            ->orderBy('name')
            ->get();

        return view('admin.modules.create', compact('levels', 'skillCategories', 'methods', 'teachers'));
    }

    public function store(StoreModuleRequest $request)
    {
        $module = Module::create($request->validated());
        $module->trainers()->sync($request->input('trainer_ids', []));

        if ($module->career_level_id) {
            $careerLevel = CareerLevel::find($module->career_level_id);
            return redirect()
                ->route('admin.paths.show', $careerLevel->career_path_id)
                ->with('success', "Modul \"{$module->title}\" wurde erstellt!");
        }

        if ($module->skill_category_id) {
            return redirect()
                ->route('admin.skill-categories.show', $module->skill_category_id)
                ->with('success', "Modul \"{$module->title}\" wurde erstellt!");
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Modul \"{$module->title}\" wurde erstellt!");
    }

    public function edit(Module $module)
    {
        $module->load('quiz', 'trainers');
        $levels = CareerLevel::with('careerPath')->get()
            ->groupBy('careerPath.name');
        $skillCategories = SkillCategory::orderBy('name')->get();
        $methods = Method::orderBy('name')->get();
        $teachers = User::active()
            ->whereHas('roles', fn ($q) => $q->whereIn('slug', ['admin', 'people_manager', 'head_of', 'trainer']))
            ->orderBy('name')
            ->get();

        return view('admin.modules.edit', compact('module', 'levels', 'skillCategories', 'methods', 'teachers'));
    }

    public function update(StoreModuleRequest $request, Module $module)
    {
        $module->update($request->validated());
        $module->trainers()->sync($request->input('trainer_ids', []));

        return $this->redirectToModuleContainer($module, "Modul \"{$module->title}\" wurde aktualisiert!");
    }

    public function destroy(Module $module)
    {
        $title = $module->title;
        $careerLevelId = $module->career_level_id;
        $skillCategoryId = $module->skill_category_id;
        $module->delete();

        if ($careerLevelId) {
            $careerLevel = CareerLevel::find($careerLevelId);
            if ($careerLevel) {
                return redirect()
                    ->route('admin.paths.show', $careerLevel->career_path_id)
                    ->with('success', "Modul \"{$title}\" wurde gelöscht.");
            }
        }

        if ($skillCategoryId) {
            return redirect()
                ->route('admin.skill-categories.show', $skillCategoryId)
                ->with('success', "Modul \"{$title}\" wurde gelöscht.");
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Modul \"{$title}\" wurde gelöscht.");
    }

    private function redirectToModuleContainer(Module $module, string $message)
    {
        if ($module->career_level_id) {
            return redirect()
                ->route('admin.paths.show', $module->careerLevel->career_path_id)
                ->with('success', $message);
        }

        if ($module->skill_category_id) {
            return redirect()
                ->route('admin.skill-categories.show', $module->skill_category_id)
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.modules.index')
            ->with('success', $message);
    }

    public function destroyPath(CareerPath $path)
    {
        $name = $path->name;
        $moduleCount = $path->modules()->count();
        $path->delete();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Karrierepfad \"{$name}\" mit allen Stufen und {$moduleCount} Modulen wurde gelöscht.");
    }

    public function createPath()
    {
        return view('admin.modules.create-path');
    }

    public function editPath(CareerPath $path)
    {
        $path->load('levels.modules');

        return view('admin.modules.edit-path', compact('path'));
    }

    public function updatePath(Request $request, CareerPath $path)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'emoji' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:2000'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.id' => ['nullable', 'integer'],
            'levels.*.title' => ['required', 'string', 'max:255'],
            'levels.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        $path->update([
            'name' => $request->name,
            'emoji' => $request->emoji,
            'description' => $request->description,
        ]);

        $existingLevelIds = $path->levels->pluck('id')->toArray();
        $submittedIds = collect($request->levels)
            ->pluck('id')
            ->filter()
            ->toArray();

        $toDelete = array_diff($existingLevelIds, $submittedIds);
        if (!empty($toDelete)) {
            CareerLevel::whereIn('id', $toDelete)
                ->where('career_path_id', $path->id)
                ->delete();
        }

        foreach ($request->levels as $index => $levelData) {
            if (!empty($levelData['id'])) {
                $level = CareerLevel::where('id', $levelData['id'])
                    ->where('career_path_id', $path->id)
                    ->first();

                if ($level) {
                    $level->update([
                        'level_number' => $index + 1,
                        'title' => $levelData['title'],
                        'description' => $levelData['description'] ?? null,
                    ]);
                    continue;
                }
            }

            $path->levels()->create([
                'level_number' => $index + 1,
                'title' => $levelData['title'],
                'description' => $levelData['description'] ?? null,
            ]);
        }

        return redirect()
            ->route('admin.paths.show', $path)
            ->with('success', "Karrierepfad \"{$path->name}\" wurde aktualisiert!");
    }

    public function storePath(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'emoji' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:2000'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.title' => ['required', 'string', 'max:255'],
            'levels.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        $path = CareerPath::create([
            'name' => $request->name,
            'emoji' => $request->emoji,
            'description' => $request->description,
        ]);

        foreach ($request->levels as $index => $levelData) {
            $path->levels()->create([
                'level_number' => $index + 1,
                'title' => $levelData['title'],
                'description' => $levelData['description'] ?? null,
            ]);
        }

        return redirect()
            ->route('admin.paths.show', $path)
            ->with('success', "Karrierepfad \"{$path->name}\" mit " . count($request->levels) . " Stufen erstellt!");
    }
}
