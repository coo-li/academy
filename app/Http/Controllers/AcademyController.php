<?php

namespace App\Http\Controllers;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Enrollment;
use App\Models\Module;
use App\Services\PersonioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademyController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $user->load(['careerLevel.careerPath', 'enrollments.module', 'enrollments.trainingSession', 'disabledCareerModules']);

        $careerLevel = $user->careerLevel;
        $careerPath = $careerLevel?->careerPath;

        $eagerLoad = ['trainingSessions', 'quiz', 'method', 'accountableUser', 'careerLevel'];

        $careerModules = $careerLevel
            ? $careerLevel->modules()->with($eagerLoad)->orderBy('sort_order')->get()
            : collect();

        $disabledIds = $user->disabledCareerModules->pluck('id')->toArray();
        $activeCareerModules = $careerModules->reject(fn ($m) => in_array($m->id, $disabledIds));

        $assignedModules = $user->assignedModules()->with($eagerLoad)->orderBy('sort_order')->get();

        $modules = $activeCareerModules->merge($assignedModules)->unique('id')->sortBy('sort_order')->values();

        $enrollmentsByModule = $user->enrollments->keyBy('module_id');

        $stats = [
            'total' => $modules->count(),
            'completed' => $user->enrollments->where('status', 'completed')->count(),
            'enrolled' => $user->enrollments->where('status', 'enrolled')->count(),
            'attended' => $user->enrollments->where('status', 'attended')->count(),
        ];

        $nextLevel = $careerLevel
            ? CareerLevel::where('career_path_id', $careerLevel->career_path_id)
                ->where('level_number', $careerLevel->level_number + 1)
                ->first()
            : null;

        $personioStats = null;
        if ($user->isAdmin()) {
            $personioStats = [
                'users_without_path' => PersonioService::getUsersWithoutCareerPath(),
                'last_sync' => PersonioService::getLastSync(),
            ];
        }

        return view('academy.dashboard', compact(
            'user', 'careerLevel', 'careerPath', 'modules',
            'enrollmentsByModule', 'stats', 'nextLevel', 'personioStats'
        ));
    }

    public function timeline()
    {
        $user = Auth::user();
        $user->load('careerLevel.careerPath');

        $enrollments = Enrollment::with(['module', 'trainingSession'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $activeEnrollments = $enrollments->filter(fn ($e) => $e->isActive());
        $historyEnrollments = $enrollments->filter(fn ($e) => !$e->isActive());

        return view('academy.timeline', compact('user', 'enrollments', 'activeEnrollments', 'historyEnrollments'));
    }
}
