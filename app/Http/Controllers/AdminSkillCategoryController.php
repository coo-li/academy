<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skill_categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        SkillCategory::create($request->only('name', 'description'));

        return redirect()
            ->route('admin.skill-categories.index')
            ->with('success', "Kategorie \"{$request->name}\" wurde erstellt.");
    }

    public function update(Request $request, SkillCategory $skillCategory)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:skill_categories,name,' . $skillCategory->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $skillCategory->update($request->only('name', 'description'));

        return redirect()
            ->route('admin.skill-categories.index')
            ->with('success', "Kategorie \"{$skillCategory->name}\" wurde aktualisiert.");
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
            ->route('admin.skill-categories.index')
            ->with('success', "Kategorie \"{$name}\" wurde gelöscht." .
                ($moduleCount > 0 ? " {$moduleCount} Module wurden entkoppelt." : ''));
    }
}
