<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingSessionRequest;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TrainerTerminController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $moduleIds = $this->trainerModuleIds($user);

        $modules = Module::with('careerLevel.careerPath')
            ->whereIn('id', $moduleIds)
            ->orderBy('title')
            ->get();

        $sessions = TrainingSession::with(['module.method', 'enrollments.user'])
            ->whereIn('module_id', $moduleIds)
            ->orderBy('start_at', 'desc')
            ->paginate(15);

        return view('trainer.termine', compact('modules', 'sessions'));
    }

    public function store(StoreTrainingSessionRequest $request)
    {
        $session = TrainingSession::create($request->validated());

        try {
            $calendarService = app(GoogleCalendarService::class);
            $calendarId = config('services.google.calendar_id', 'primary');
            $module = Module::find($request->module_id);

            $event = $calendarService->createEvent(
                $calendarId,
                "Workshop: {$module->title}",
                $session->start_at,
                $session->end_at,
                "Academy Workshop\nModul: {$module->title}",
                $session->location,
            );

            $session->update(['google_event_id' => $event->getId()]);

            $trainer = Auth::user();
            $calendarService->addAttendee($calendarId, $event->getId(), $trainer->email, $trainer->name);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar Event konnte nicht erstellt werden.', [
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Workshop-Termin erfolgreich erstellt!');
    }

    public function destroy(TrainingSession $session)
    {
        if ($session->google_event_id) {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $calendarId = config('services.google.calendar_id', 'primary');
                $calendarService->deleteEvent($calendarId, $session->google_event_id);
            } catch (\Throwable $e) {
                Log::warning('Google Calendar Event konnte nicht gelöscht werden.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $session->delete();

        return back()->with('success', 'Termin gelöscht.');
    }

    /**
     * Module IDs where the current user is the accountable trainer.
     * Admins see all modules.
     */
    private function trainerModuleIds($user): array
    {
        if ($user->isAdmin()) {
            return Module::pluck('id')->toArray();
        }

        return Module::where('accountable_type', 'user')
            ->where('accountable_user_id', $user->id)
            ->pluck('id')
            ->toArray();
    }
}
