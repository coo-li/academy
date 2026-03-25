<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\SkillCategory;
use Illuminate\Http\Request;

class AdminSkillCategoryController extends Controller
{
    public function index()
    {
        $categories = SkillCategory::withCount('modules')
            ->orderBy('name')
            ->get();

        return view('admin.skill-categories.index', compact('categories'));
    }

    public function show(SkillCategory $skillCategory)
    {
        $modules = Module::where('skill_category_id', $skillCategory->id)
            ->with(['careerLevel.careerPath', 'accountableUser', 'method', 'quiz'])
            ->orderBy('sort_order')
            ->get();

        return view('admin.skill-categories.show', compact('skillCategory', 'modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skill_categories,name'],
            'emoji' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = SkillCategory::create($request->only('name', 'emoji', 'description'));

        return redirect()
            ->route('admin.skill-categories.show', $category)
            ->with('success', "Skill-Gruppe \"{$category->name}\" wurde erstellt.");
    }

    public function update(Request $request, SkillCategory $skillCategory)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skill_categories,name,' . $skillCategory->id],
            'emoji' => ['nullable', 'string', 'max:8'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $skillCategory->update($request->only('name', 'emoji', 'description'));

        return redirect()
            ->route('admin.skill-categories.show', $skillCategory)
            ->with('success', "Skill-Gruppe \"{$skillCategory->name}\" wurde aktualisiert.");
    }

    public function destroy(SkillCategory $skillCategory)
    {
        $name = $skillCategory->name;
        $moduleCount = $skillCategory->modules()->count();

        if ($moduleCount > 0) {
            $skillCategory->modules()->update(['skill_category_id' => null]);
        }

        $skillCategory->delete();

        return redirect()
            ->route('admin.modules.index')
            ->with('success', "Skill-Gruppe \"{$name}\" wurde gelöscht." .
                ($moduleCount > 0 ? " {$moduleCount} Module wurden entkoppelt." : ''));
    }
}
