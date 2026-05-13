<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollRequest;
use App\Models\CareerLevel;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Services\EnrollmentService;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    public function __construct(
        protected EnrollmentService $enrollmentService,
    ) {}

    public function store(EnrollRequest $request)
    {
        $user = Auth::user();
        $module = Module::with('method')->findOrFail($request->module_id);

        $existing = Enrollment::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->whereIn('status', ['enrolled', 'attended', 'completed', 'requested'])
            ->first();

        if ($existing) {
            $label = $existing->status === 'requested'
                ? 'Du hast bereits eine Terminanfrage für dieses Modul gestellt.'
                : 'Du bist bereits für dieses Modul eingeschrieben.';
            return back()->with('error', $label);
        }

        $enrollment = $this->enrollmentService->enroll(
            $user,
            $module,
            $request->training_session_id,
        );

        $message = match ($enrollment->status) {
            'requested' => 'Terminanfrage gesendet! Der Trainer wird sich mit einem Terminvorschlag melden.',
            default => 'Erfolgreich eingebucht! Dein People Manager wurde via Asana informiert.',
        };

        return back()->with('success', $message);
    }

    public function cancel(Enrollment $enrollment)
    {
        if ($enrollment->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $enrollment->isActive() && $enrollment->status !== 'requested') {
            return back()->with('error', 'Diese Buchung kann nicht mehr storniert werden.');
        }

        $user = Auth::user();
        $enrollment->load(['module', 'trainingSession']);

        $this->enrollmentService->cancel($enrollment, $user);

        return back()->with('success', "Buchung für \"{$enrollment->module->title}\" wurde storniert.");
    }

    public function rebook(Enrollment $enrollment, EnrollRequest $request)
    {
        if ($enrollment->user_id !== Auth::id()) {
            abort(403);
        }

        if ($enrollment->status !== 'enrolled') {
            return back()->with('error', 'Umbuchen ist nur möglich, solange die Teilnahme noch nicht bestätigt wurde.');
        }

        $user = Auth::user();
        $newSession = TrainingSession::findOrFail($request->training_session_id);

        $this->enrollmentService->rebook($enrollment, $newSession, $user);

        return back()->with('success', "Erfolgreich umgebucht auf den {$newSession->start_at->format('d.m.Y, H:i')} Uhr.");
    }

    protected function checkLevelCompletion(int $userId): void
    {
        $user = \App\Models\User::with('careerLevels')->find($userId);
        if (! $user || $user->careerLevels->isEmpty()) {
            return;
        }

        foreach ($user->careerLevels as $level) {
            $mandatoryIds = $level->modules()->where('is_mandatory', true)->pluck('id');
            $completedIds = Enrollment::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereIn('module_id', $mandatoryIds)
                ->pluck('module_id');

            if ($mandatoryIds->isNotEmpty() && $mandatoryIds->diff($completedIds)->isEmpty()) {
                $nextLevel = CareerLevel::where('career_path_id', $level->career_path_id)
                    ->where('level_number', $level->level_number + 1)
                    ->first();

                if ($nextLevel) {
                    $user->replaceCareerLevel($level, $nextLevel);
                }
            }
        }
    }
}
