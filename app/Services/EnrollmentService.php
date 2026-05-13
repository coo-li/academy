<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Method;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class EnrollmentService
{
    public function __construct(
        protected AsanaService $asana,
        protected GoogleCalendarService $calendar,
    ) {}

    /**
     * Enroll a user based on the module's scheduling type.
     */
    public function enroll(User $user, Module $module, ?int $trainingSessionId = null): Enrollment
    {
        $schedulingType = $module->method?->scheduling_type ?? Method::TYPE_SCHEDULED;

        return match ($schedulingType) {
            Method::TYPE_SELF_STUDY => $this->enrollSelfStudy($user, $module),
            Method::TYPE_REQUEST => $this->enrollRequest($user, $module),
            default => $this->enrollScheduled($user, $module, $trainingSessionId),
        };
    }

    /**
     * Scheduled: participant picks an existing session.
     */
    public function enrollScheduled(User $user, Module $module, ?int $trainingSessionId): Enrollment
    {
        $session = TrainingSession::findOrFail($trainingSessionId);
        $enrollment = $this->findOrCreateEnrollment($user, $module, [
            'training_session_id' => $session->id,
            'status' => 'enrolled',
        ]);

        $this->syncCalendarAttendee($user, $session);
        $this->createAsanaBookingTask($enrollment, $user, $module, $session);

        return $enrollment;
    }

    /**
     * Self-study: no session needed, materials immediately available.
     */
    public function enrollSelfStudy(User $user, Module $module): Enrollment
    {
        $enrollment = $this->findOrCreateEnrollment($user, $module, [
            'training_session_id' => null,
            'status' => 'enrolled',
        ]);

        $this->createAsanaBookingTask($enrollment, $user, $module, null);

        return $enrollment;
    }

    /**
     * Request: participant requests a session, trainer gets notified.
     */
    public function enrollRequest(User $user, Module $module): Enrollment
    {
        $enrollment = $this->findOrCreateEnrollment($user, $module, [
            'training_session_id' => null,
            'status' => 'requested',
        ]);

        $this->createAsanaRequestTask($enrollment, $user, $module);

        return $enrollment;
    }

    /**
     * Assign a session to a requested enrollment (trainer action).
     */
    public function assignSession(Enrollment $enrollment, TrainingSession $session): Enrollment
    {
        $enrollment->update([
            'training_session_id' => $session->id,
            'status' => 'enrolled',
        ]);

        $user = $enrollment->user;
        $this->syncCalendarAttendee($user, $session);

        return $enrollment;
    }

    /**
     * Cancel an enrollment.
     */
    public function cancel(Enrollment $enrollment, User $user): Enrollment
    {
        $enrollment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $this->cancelAsanaTask($enrollment, $user);
        $this->removeCalendarAttendee($user, $enrollment->trainingSession);

        return $enrollment;
    }

    /**
     * Rebook an enrollment to a different session.
     */
    public function rebook(Enrollment $enrollment, TrainingSession $newSession, User $user): Enrollment
    {
        $oldSession = $enrollment->trainingSession;

        $enrollment->update([
            'training_session_id' => $newSession->id,
        ]);

        $this->removeCalendarAttendee($user, $oldSession);
        $this->syncCalendarAttendee($user, $newSession);

        return $enrollment;
    }

    protected function findOrCreateEnrollment(User $user, Module $module, array $data): Enrollment
    {
        $cancelled = Enrollment::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', 'cancelled')
            ->first();

        if ($cancelled) {
            $cancelled->update(array_merge($data, [
                'cancelled_at' => null,
                'completed_at' => null,
                'attendance_confirmed_at' => null,
                'attendance_confirmed_by' => null,
            ]));
            return $cancelled;
        }

        return Enrollment::create(array_merge([
            'user_id' => $user->id,
            'module_id' => $module->id,
        ], $data));
    }

    // ------------------------------------------------------------------
    // Google Calendar helpers
    // ------------------------------------------------------------------

    protected function syncCalendarAttendee(User $user, ?TrainingSession $session): void
    {
        if (! $session) {
            return;
        }

        try {
            $calendarId = config('services.google.calendar_id', 'primary');

            if (! $session->google_event_id) {
                $this->createCalendarEventForSession($session, $calendarId);
            }

            if (! $session->google_event_id) {
                return;
            }

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

    protected function createCalendarEventForSession(TrainingSession $session, string $calendarId): void
    {
        if (! $this->calendar->isConfigured()) {
            return;
        }

        try {
            $module = $session->module;
            $event = $this->calendar->createEvent(
                $calendarId,
                "Workshop: {$module->title}",
                $session->start_at,
                $session->end_at,
                "Academy Workshop – {$module->title}",
                $session->location,
            );

            if ($event) {
                $session->update(['google_event_id' => $event->getId()]);
            }
        } catch (\Throwable $e) {
            Log::warning('Google Calendar: Could not create event for session.', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function removeCalendarAttendee(User $user, ?TrainingSession $session): void
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

    // ------------------------------------------------------------------
    // Asana helpers
    // ------------------------------------------------------------------

    protected function createAsanaBookingTask(Enrollment $enrollment, User $user, Module $module, ?TrainingSession $session): void
    {
        try {
            $assigneeEmail = $user->getPeopleManager()?->email;
            $taskData = $this->asana->createBookingTask($user, $module, $session, $assigneeEmail);

            if ($taskData && isset($taskData['gid'])) {
                $enrollment->update(['asana_task_gid' => $taskData['gid']]);
            }
        } catch (\Throwable $e) {
            Log::warning('Asana: Task creation failed during enrollment.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function createAsanaRequestTask(Enrollment $enrollment, User $user, Module $module): void
    {
        try {
            $assigneeEmail = $module->accountableUser?->email ?? $user->getPeopleManager()?->email;
            $taskData = $this->asana->createRequestTask($user, $module, $assigneeEmail);

            if ($taskData && isset($taskData['gid'])) {
                $enrollment->update(['asana_task_gid' => $taskData['gid']]);
            }
        } catch (\Throwable $e) {
            Log::warning('Asana: Request task creation failed.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function cancelAsanaTask(Enrollment $enrollment, User $user): void
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
}
