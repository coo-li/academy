<?php

namespace App\Http\Controllers;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\Enrollment;
use App\Models\Milestone;
use App\Models\Module;
use App\Services\PersonioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcademyController extends Controller
{
    public function dashboard()
    {
        $user = Auth::user();
        $user->load([
            'careerLevel.careerPath',
            'careerLevels.careerPath',
            'enrollments.module',
            'enrollments.trainingSession',
            'disabledCareerModules',
            'disabledMilestones',
            'moduleInterests.module',
        ]);

        $careerLevel = $user->careerLevel;
        $careerPath = $careerLevel?->careerPath;
        $careerLevels = $user->careerLevels;

        $eagerLoad = ['trainingSessions', 'quiz', 'method', 'accountableUser', 'careerLevel.careerPath'];

        $careerModules = $careerLevels
            ->flatMap(fn ($level) => $level->modules()->with($eagerLoad)->orderBy('sort_order')->get())
            ->unique('id');

        $disabledIds = $user->disabledCareerModules->pluck('id')->toArray();
        $activeCareerModules = $careerModules->reject(fn ($m) => in_array($m->id, $disabledIds));

        $assignedModules = $user->assignedModules()->with($eagerLoad)->orderBy('sort_order')->get();

        $modules = $activeCareerModules->merge($assignedModules)->unique('id')->sortBy('sort_order')->values();

        $enrollmentsByModule = $user->enrollments->keyBy('module_id');

        $moduleIds = $modules->pluck('id')->toArray();
        $interestedModules = $user->moduleInterests
            ->filter(fn ($interest) => !in_array($interest->module_id, $moduleIds))
            ->map(function ($interest) use ($eagerLoad) {
                $module = $interest->module;
                if ($module) {
                    $module->load($eagerLoad);
                }
                return $module;
            })
            ->filter()
            ->values();

        $interestsByModule = $user->moduleInterests->keyBy('module_id');

        $stats = [
            'total' => $modules->count(),
            'completed' => $user->enrollments->where('status', 'completed')->count(),
            'enrolled' => $user->enrollments->where('status', 'enrolled')->count(),
            'attended' => $user->enrollments->where('status', 'attended')->count(),
            'interested' => $interestedModules->count(),
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

        $disabledMilestoneIds = $user->disabledMilestones->pluck('id')->toArray();
        $milestonesByCategory = Milestone::forUser($user)
            ->orderBy('sort_order')
            ->get()
            ->reject(fn ($m) => in_array($m->id, $disabledMilestoneIds))
            ->groupBy('category');

        $upcomingTermine = $user->enrollments
            ->filter(fn ($e) => $e->isActive() && $e->trainingSession && $e->trainingSession->start_at->isFuture())
            ->sortBy(fn ($e) => $e->trainingSession->start_at)
            ->take(10)
            ->values();

        $assignedModuleIds = $assignedModules->pluck('id')->toArray();

        $userPaths = $careerLevels
            ->map(fn ($level) => $level->careerPath)
            ->filter()
            ->unique('id')
            ->values();

        return view('academy.dashboard', compact(
            'user', 'careerLevel', 'careerPath', 'careerLevels', 'modules',
            'enrollmentsByModule', 'interestedModules', 'interestsByModule',
            'stats', 'nextLevel', 'personioStats', 'milestonesByCategory',
            'upcomingTermine', 'assignedModuleIds', 'userPaths'
        ));
    }

    public function showModule(Module $module)
    {
        $user = Auth::user();

        $module->load([
            'trainingSessions.enrollments.user',
            'trainingSessions.trainer',
            'quiz',
            'method',
            'skillCategory',
            'trainingMaterials.uploader',
            'accountableUser',
            'careerLevel.careerPath',
        ]);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->latest()
            ->first();

        $status = $enrollment?->status ?? 'open';
        $schedulingType = $module->method?->scheduling_type ?? 'scheduled';

        $upcomingSessions = $schedulingType === 'scheduled'
            ? $module->trainingSessions
                ->where('start_at', '>', now())
                ->sortBy('start_at')
                ->values()
            : collect();

        $bookedSession = $enrollment?->trainingSession;

        $sessionParticipants = $bookedSession
            ? $bookedSession->enrollments()
                ->whereIn('status', ['enrolled', 'attended', 'completed'])
                ->with('user:id,name')
                ->get()
                ->pluck('user')
            : collect();

        $canViewMaterials = $schedulingType === 'self_study'
            ? in_array($status, ['enrolled', 'attended', 'completed'])
            : in_array($status, ['attended', 'completed']);

        $accountable = $module->getAccountableFor($user);

        return view('academy.module-show', compact(
            'module', 'user', 'enrollment', 'status', 'schedulingType',
            'upcomingSessions', 'bookedSession', 'sessionParticipants',
            'canViewMaterials', 'accountable'
        ));
    }

    public function timeline()
    {
        $user = Auth::user();
        $user->load(['careerLevel.careerPath', 'careerLevels.careerPath']);

        $enrollments = Enrollment::with(['module', 'trainingSession'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();

        $activeEnrollments = $enrollments->filter(fn ($e) => $e->isActive());
        $historyEnrollments = $enrollments->filter(fn ($e) => !$e->isActive());

        return view('academy.timeline', compact('user', 'enrollments', 'activeEnrollments', 'historyEnrollments'));
    }
}
