<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingSessionRequest;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TeacherController extends Controller
{
    public function dashboard()
    {

        $modules = Module::with('careerLevel.careerPath')->orderBy('title')->get();
        $sessions = TrainingSession::with(['module.method', 'enrollments.user'])
            ->orderBy('start_at', 'desc')
            ->paginate(15);

        return view('teacher.dashboard', compact('modules', 'sessions'));
    }

    public function storeSession(StoreTrainingSessionRequest $request)
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

            $teacher = Auth::user();
            $calendarService->addAttendee($calendarId, $event->getId(), $teacher->email, $teacher->name);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar Event konnte nicht erstellt werden.', [
                'error' => $e->getMessage(),
            ]);
        }

        return back()->with('success', 'Workshop-Termin erfolgreich erstellt!');
    }

    public function confirmAttendance(TrainingSession $session, Request $request)
    {
        $request->validate([
            'attendees' => ['required', 'array', 'min:1'],
            'attendees.*' => ['integer', 'exists:users,id'],
        ]);

        $confirmed = Enrollment::where('training_session_id', $session->id)
            ->where('status', 'enrolled')
            ->whereIn('user_id', $request->attendees)
            ->get();

        foreach ($confirmed as $enrollment) {
            $enrollment->update([
                'status' => 'attended',
                'attendance_confirmed_at' => now(),
                'attendance_confirmed_by' => Auth::id(),
            ]);
        }

        $count = $confirmed->count();

        return back()->with('success', "Anwesenheit für {$count} Teilnehmer bestätigt.");
    }

    public function destroySession(TrainingSession $session)
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
}
