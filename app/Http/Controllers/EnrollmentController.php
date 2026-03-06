<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollRequest;
use App\Models\CareerLevel;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Services\AsanaService;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class EnrollmentController extends Controller
{
    public function __construct(
        protected AsanaService $asana,
        protected GoogleCalendarService $calendar,
    ) {}

    public function store(EnrollRequest $request)
    {
        $user = Auth::user();
        $module = Module::with('trainingSessions')->findOrFail($request->module_id);

        $existing = Enrollment::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->whereIn('status', ['enrolled', 'attended', 'completed'])
            ->first();

        if ($existing) {
            return back()->with('error', 'Du bist bereits für dieses Modul eingeschrieben.');
        }

        $session = TrainingSession::findOrFail($request->training_session_id);

        $cancelled = Enrollment::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', 'cancelled')
            ->first();

        if ($cancelled) {
            $cancelled->update([
                'training_session_id' => $session->id,
                'status' => 'enrolled',
                'cancelled_at' => null,
                'completed_at' => null,
                'attendance_confirmed_at' => null,
                'attendance_confirmed_by' => null,
            ]);
            $enrollment = $cancelled;
        } else {
            $enrollment = Enrollment::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'training_session_id' => $session->id,
                'status' => 'enrolled',
            ]);
        }

        $this->syncCalendarAttendee($user, $session);
        $this->createAsanaBookingTask($enrollment, $user, $module, $session);

        return back()->with('success', 'Erfolgreich eingebucht! Dein People Manager wurde via Asana informiert.');
    }

    public function cancel(Enrollment $enrollment)
    {
        if ($enrollment->user_id !== Auth::id()) {
            abort(403);
        }

        if (! $enrollment->isActive()) {
            return back()->with('error', 'Diese Buchung kann nicht mehr storniert werden.');
        }

        $user = Auth::user();
        $enrollment->load(['module', 'trainingSession']);

        $enrollment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->cancelAsanaTask($enrollment, $user);
        $this->removeCalendarAttendee($user, $enrollment->trainingSession);

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
        $oldSession = $enrollment->trainingSession;
        $newSession = TrainingSession::findOrFail($request->training_session_id);

        $enrollment->update([
            'training_session_id' => $newSession->id,
        ]);

        $this->removeCalendarAttendee($user, $oldSession);
        $this->syncCalendarAttendee($user, $newSession);

        return back()->with('success', "Erfolgreich umgebucht auf den {$newSession->start_at->format('d.m.Y, H:i')} Uhr.");
    }

    protected function syncCalendarAttendee($user, ?TrainingSession $session): void
    {
        if (! $session?->google_event_id) {
            return;
        }

        try {
            $calendarId = config('services.google.calendar_id', 'primary');
            $this->calendar->addAttendee(
                $calendarId,
                $session->google_event_id,
                $user->email,
                $user->name,
            );
        } catch (\Throwable $e) {
            Log::warning('Google Calendar: Could not add attendee to event.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function removeCalendarAttendee($user, ?TrainingSession $session): void
    {
        if (! $session?->google_event_id) {
            return;
        }

        try {
            $calendarId = config('services.google.calendar_id', 'primary');
            $this->calendar->removeAttendee(
                $calendarId,
                $session->google_event_id,
                $user->email,
            );
        } catch (\Throwable $e) {
            Log::warning('Google Calendar: Could not remove attendee from event.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function createAsanaBookingTask(Enrollment $enrollment, $user, Module $module, ?TrainingSession $session): void
    {
        try {
            $taskData = $this->asana->createBookingTask($user, $module, $session);

            if ($taskData && isset($taskData['gid'])) {
                $enrollment->update(['asana_task_gid' => $taskData['gid']]);
            }
        } catch (\Throwable $e) {
            Log::warning('Asana: Task creation failed during enrollment.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function cancelAsanaTask(Enrollment $enrollment, $user): void
    {
        if (! $enrollment->asana_task_gid) {
            return;
        }

        try {
            $this->asana->addComment(
                $enrollment->asana_task_gid,
                "⚠️ Buchung storniert von {$user->name} am " . now()->format('d.m.Y, H:i') . " Uhr.",
            );
            $this->asana->completeTask($enrollment->asana_task_gid);
        } catch (\Throwable $e) {
            Log::warning('Asana: Could not update task on cancellation.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function checkLevelCompletion(int $userId): void
    {
        $user = \App\Models\User::with('careerLevel')->find($userId);
        if (! $user?->careerLevel) {
            return;
        }

        $levelModules = $user->careerLevel->modules()->where('is_mandatory', true)->pluck('id');
        $completedModules = Enrollment::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereIn('module_id', $levelModules)
            ->pluck('module_id');

        if ($levelModules->diff($completedModules)->isEmpty()) {
            $nextLevel = CareerLevel::where('career_path_id', $user->careerLevel->career_path_id)
                ->where('level_number', $user->careerLevel->level_number + 1)
                ->first();

            if ($nextLevel) {
                $user->update(['career_level_id' => $nextLevel->id]);
            }
        }
    }
}
