<?php

namespace App\Http\Controllers;

use App\Models\CareerPath;
use App\Models\Method;
use App\Models\Module;
use App\Models\SkillCategory;
use App\Notifications\NewModuleInterest;
use App\Services\ModuleInterestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillOverviewController extends Controller
{
    public function __construct(
        protected ModuleInterestService $interestService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Module::with([
            'careerLevel.careerPath',
            'skillCategory',
            'method',
            'trainingSessions' => fn ($q) => $q->where('start_at', '>', now())->orderBy('start_at'),
        ]);

        $userPathIds = $user->careerLevels()
            ->pluck('career_path_id')
            ->unique()
            ->toArray();

        $leadershipPath = CareerPath::where('name', 'Leadership')->first();

        if ($leadershipPath && ! in_array($leadershipPath->id, $userPathIds)) {
            $query->whereDoesntHave('careerLevel', fn ($sub) =>
                $sub->where('career_path_id', $leadershipPath->id)
            );
        }

        if ($request->filled('career_path')) {
            $query->whereHas('careerLevel', fn ($q) => $q->where('career_path_id', $request->career_path));
        }

        if ($request->filled('skill_category')) {
            $query->where('skill_category_id', $request->skill_category);
        }

        if ($request->filled('method')) {
            $query->where('method_id', $request->method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $modules = $query->orderBy('title')->paginate(24)->withQueryString();

        $enrollmentsByModule = $user->enrollments()
            ->whereIn('module_id', $modules->pluck('id'))
            ->get()
            ->keyBy('module_id');

        $interestsByModule = $user->moduleInterests()
            ->whereIn('module_id', $modules->pluck('id'))
            ->get()
            ->keyBy('module_id');

        $careerPaths = CareerPath::orderBy('name')->get();
        $skillCategories = SkillCategory::orderBy('name')->get();
        $methods = Method::orderBy('name')->get();

        return view('academy.skill-overview', compact(
            'modules',
            'enrollmentsByModule',
            'interestsByModule',
            'careerPaths',
            'skillCategories',
            'methods',
        ));
    }

    public function expressInterest(Module $module)
    {
        $user = Auth::user();

        $existing = $user->enrollments()
            ->where('module_id', $module->id)
            ->whereIn('status', ['enrolled', 'attended', 'completed'])
            ->exists();

        if ($existing) {
            return back()->with('error', 'Du bist bereits für dieses Modul eingeschrieben.');
        }

        $this->interestService->express($user, $module);

        $manager = $user->getPeopleManager();
        if ($manager) {
            $manager->notify(new NewModuleInterest($module, $user));
        }

        return back()->with('success', "Interesse an \"{$module->title}\" wurde gespeichert. Dein People Manager wird benachrichtigt.");
    }

    public function withdrawInterest(Module $module)
    {
        $this->interestService->withdraw(Auth::user(), $module);

        return back()->with('success', "Interesse an \"{$module->title}\" wurde zurückgezogen.");
    }
}
