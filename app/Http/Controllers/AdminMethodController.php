<?php

namespace App\Http\Controllers;

use App\Models\Method;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminMethodController extends Controller
{
    public function index()
    {
        $methods = Method::withCount('modules')
            ->orderBy('name')
            ->get();

        $schedulingTypes = Method::SCHEDULING_LABELS;

        return view('admin.methods.index', compact('methods', 'schedulingTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:methods,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'scheduling_type' => ['required', Rule::in(Method::SCHEDULING_TYPES)],
        ]);

        Method::create($request->only('name', 'description', 'scheduling_type'));

        return redirect()
            ->route('admin.methods.index')
            ->with('success', "Methode \"{$request->name}\" wurde erstellt.");
    }

    public function update(Request $request, Method $method)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:methods,name,' . $method->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'scheduling_type' => ['required', Rule::in(Method::SCHEDULING_TYPES)],
        ]);

        $method->update($request->only('name', 'description', 'scheduling_type'));

        return redirect()
            ->route('admin.methods.index')
            ->with('success', "Methode \"{$method->name}\" wurde aktualisiert.");
    }

    public function destroy(Method $method)
    {
        $name = $method->name;
        $moduleCount = $method->modules()->count();

        if ($moduleCount > 0) {
            $method->modules()->update(['method_id' => null]);
        }

        $method->delete();

        return redirect()
            ->route('admin.methods.index')
            ->with('success', "Methode \"{$name}\" wurde gelöscht." .
                ($moduleCount > 0 ? " {$moduleCount} Module wurden entkoppelt." : ''));
    }
}
