<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreModuleRequest;
use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Method;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\SkillCategory;
use App\Models\User;
use Illuminate\Http\Request;

class AdminModuleController extends Controller
{
    public function index()
    {
        $paths = CareerPath::with(['levels.modules' => fn ($q) => $q->with(['skillCategory', 'accountableUser', 'method'])->orderBy('sort_order')])
            ->get();

        $globalModules = Module::whereNull('career_level_id')
            ->with(['skillCategory', 'accountableUser', 'method'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.modules.index', compact('paths', 'globalModules'));
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

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Modul \"{$module->title}\" wurde erstellt!");
    }

    public function edit(Module $module)
    {
        $module->load('quiz');
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

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Modul \"{$module->title}\" wurde aktualisiert!");
    }

    public function destroy(Module $module)
    {
        $title = $module->title;
        $module->delete();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Modul \"{$title}\" wurde gelöscht.");
    }

    public function storeQuiz(Request $request, Module $module)
    {
        $request->validate([
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.question' => ['required', 'string'],
            'questions.*.options' => ['required', 'array', 'min:2'],
            'questions.*.options.*' => ['required', 'string'],
            'questions.*.correct' => ['required', 'integer', 'min:0'],
            'pass_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $module->quiz()->updateOrCreate(
            ['module_id' => $module->id],
            [
                'questions' => $request->questions,
                'pass_percentage' => $request->pass_percentage,
            ]
        );

        return back()->with('success', 'Quiz gespeichert!');
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

    public function storePath(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'levels' => ['required', 'array', 'min:1'],
            'levels.*.title' => ['required', 'string', 'max:255'],
            'levels.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        $path = CareerPath::create([
            'name' => $request->name,
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
            ->route('admin.modules.index')
            ->with('success', "Karrierepfad \"{$path->name}\" mit " . count($request->levels) . " Stufen erstellt!");
    }
}
