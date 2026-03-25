<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingSessionRequest;
use App\Http\Requests\UpdateTrainingSessionRequest;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TrainerTerminController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $scope = $request->query('scope');
        $showAll = $scope === 'all' && $user->isAdmin();

        $ownModuleIds = $this->ownTrainerModuleIds($user);
        $displayModuleIds = $showAll
            ? Module::pluck('id')->toArray()
            : $ownModuleIds;

        $modulesQuery = Module::with('careerLevel.careerPath', 'accountableUser', 'trainers');
        if ($showAll) {
            $modulesQuery->orderBy('title');
        } else {
            $modulesQuery->whereIn('id', $ownModuleIds)->orderBy('title');
        }
        $modules = $modulesQuery->get();

        $baseQuery = TrainingSession::with(['module.method', 'module.accountableUser', 'trainer', 'enrollments.user'])
            ->whereIn('module_id', $displayModuleIds);

        $today = \Carbon\Carbon::today();

        $upcomingSessions = (clone $baseQuery)
            ->where('start_at', '>=', $today)
            ->orderBy('start_at', 'asc')
            ->get();

        $pastSessions = (clone $baseQuery)
            ->where('start_at', '<', $today)
            ->orderBy('start_at', 'desc')
            ->paginate(10, ['*'], 'past_page')
            ->appends($request->query());

        $resources = [];
        try {
            $calendarService = app(GoogleCalendarService::class);
            $resources = $calendarService->listResources();
        } catch (\Throwable $e) {
            Log::debug('Google Workspace Ressourcen konnten nicht geladen werden.', ['error' => $e->getMessage()]);
        }

        $moduleTrainers = $modules->mapWithKeys(function (Module $module) {
            $pool = collect();
            if ($module->accountableUser) {
                $pool->push(['id' => $module->accountableUser->id, 'name' => $module->accountableUser->name]);
            }
            foreach ($module->trainers as $trainer) {
                if (! $pool->contains('id', $trainer->id)) {
                    $pool->push(['id' => $trainer->id, 'name' => $trainer->name]);
                }
            }
            return [$module->id => $pool->sortBy('name')->values()];
        });

        return view('trainer.termine', compact('modules', 'upcomingSessions', 'pastSessions', 'resources', 'showAll', 'moduleTrainers'));
    }

    public function store(StoreTrainingSessionRequest $request)
    {
        $startAt = $request->startAt();
        $endAt = $request->endAt();

        $location = $request->input('location');
        $resourceEmail = $request->input('resource_email');

        if ($resourceEmail && ! $location) {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $resources = $calendarService->listResources();
                foreach ($resources as $res) {
                    if ($res['email'] === $resourceEmail) {
                        $location = $res['name'];
                        break;
                    }
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        $trainerId = $request->input('trainer_id') ?: Auth::id();

        $session = TrainingSession::create([
            'module_id' => $request->module_id,
            'trainer_id' => $trainerId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'location' => $location,
            'max_participants' => $request->max_participants,
        ]);

        $calendarOk = false;

        try {
            $calendarService = app(GoogleCalendarService::class);
            $calendarId = config('services.google.calendar_id', 'primary');
            $module = Module::find($request->module_id);

            $description = trim($request->input('calendar_description', '')) ?: trim($module->calendar_description ?? '');
            $eventDescription = "Academy Workshop – {$module->title}";
            if ($description) {
                $eventDescription .= "\n\n{$description}";
            }

            $withMeet = (bool) $request->input('google_meet', false);

            $event = $calendarService->createEvent(
                $calendarId,
                "Workshop: {$module->title}",
                $session->start_at,
                $session->end_at,
                $eventDescription,
                $session->location,
                $withMeet,
                $resourceEmail,
            );

            if ($event) {
                $session->update(['google_event_id' => $event->getId()]);

                $trainer = $session->trainer;
                if ($trainer) {
                    $calendarService->addAttendee($calendarId, $event->getId(), $trainer->email, $trainer->name);
                }
                $calendarOk = true;
            }
        } catch (\Throwable $e) {
            Log::warning('Google Calendar Event konnte nicht erstellt werden.', [
                'error' => $e->getMessage(),
            ]);
        }

        if ($calendarOk) {
            return back()->with('success', 'Workshop-Termin erstellt und Google Calendar synchronisiert!');
        }

        return back()
            ->with('success', 'Workshop-Termin erstellt.')
            ->with('warning', 'Google Calendar Synchronisation fehlgeschlagen – der Termin kann nachträglich synchronisiert werden.');
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'resource_email' => ['required', 'email'],
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_date' => ['required', 'date'],
            'end_time' => ['required', 'date_format:H:i'],
        ]);

        $start = \Illuminate\Support\Carbon::parse($request->start_date . ' ' . $request->start_time);
        $end = \Illuminate\Support\Carbon::parse($request->end_date . ' ' . $request->end_time);

        try {
            $calendarService = app(GoogleCalendarService::class);
            $available = $calendarService->checkResourceAvailability($request->resource_email, $start, $end);

            return response()->json(['available' => $available]);
        } catch (\Throwable $e) {
            return response()->json(['available' => null, 'error' => 'Verfügbarkeitsprüfung fehlgeschlagen.'], 500);
        }
    }

    public function syncCalendar(TrainingSession $session)
    {
        if ($session->google_event_id) {
            return back()->with('info', 'Dieser Termin ist bereits mit Google Calendar synchronisiert.');
        }

        try {
            $calendarService = app(GoogleCalendarService::class);
            $calendarId = config('services.google.calendar_id', 'primary');
            $module = $session->module;

            $syncDescription = "Academy Workshop – {$module->title}";
            $moduleCalDesc = trim($module->calendar_description ?? '');
            if ($moduleCalDesc) {
                $syncDescription .= "\n\n{$moduleCalDesc}";
            }

            $event = $calendarService->createEvent(
                $calendarId,
                "Workshop: {$module->title}",
                $session->start_at,
                $session->end_at,
                $syncDescription,
                $session->location,
            );

            if (! $event) {
                return back()->with('error', 'Google Calendar ist nicht konfiguriert.');
            }

            $session->update(['google_event_id' => $event->getId()]);

            $trainer = $session->trainer ?? Auth::user();
            $calendarService->addAttendee($calendarId, $event->getId(), $trainer->email, $trainer->name);

            $enrolledUsers = $session->enrollments()
                ->whereIn('status', ['enrolled', 'attended', 'completed'])
                ->with('user')
                ->get();

            foreach ($enrolledUsers as $enrollment) {
                $calendarService->addAttendee(
                    $calendarId,
                    $event->getId(),
                    $enrollment->user->email,
                    $enrollment->user->name,
                );
            }

            return back()->with('success', 'Termin erfolgreich mit Google Calendar synchronisiert! ' . $enrolledUsers->count() . ' Teilnehmer hinzugefügt.');
        } catch (\Throwable $e) {
            Log::warning('Google Calendar Sync fehlgeschlagen.', ['error' => $e->getMessage()]);
            return back()->with('error', 'Kalender-Synchronisation fehlgeschlagen: ' . $e->getMessage());
        }
    }

    public function update(UpdateTrainingSessionRequest $request, TrainingSession $session)
    {
        $startAt = $request->startAt();
        $endAt = $request->endAt();

        $location = $request->input('location');
        $resourceEmail = $request->input('resource_email');

        if ($resourceEmail && ! $location) {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $resources = $calendarService->listResources();
                foreach ($resources as $res) {
                    if ($res['email'] === $resourceEmail) {
                        $location = $res['name'];
                        break;
                    }
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        $oldTrainerId = $session->trainer_id;
        $newTrainerId = $request->input('trainer_id') ?: $oldTrainerId;

        $session->update([
            'trainer_id' => $newTrainerId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'location' => $location,
            'max_participants' => $request->max_participants,
        ]);

        $calendarOk = false;

        if ($session->google_event_id) {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $calendarId = config('services.google.calendar_id', 'primary');
                $module = $session->module;

                $description = trim($request->input('calendar_description', ''));
                $calendarAttributes = [
                    'start' => $startAt,
                    'end' => $endAt,
                    'location' => $location,
                ];

                if ($description) {
                    $calendarAttributes['description'] = "Academy Workshop – {$module->title}\n\n{$description}";
                }

                $calendarService->updateEvent($calendarId, $session->google_event_id, $calendarAttributes);

                if ($oldTrainerId && $oldTrainerId != $newTrainerId) {
                    $oldTrainer = User::find($oldTrainerId);
                    if ($oldTrainer) {
                        $calendarService->removeAttendee($calendarId, $session->google_event_id, $oldTrainer->email);
                    }
                }

                if ($newTrainerId && $oldTrainerId != $newTrainerId) {
                    $newTrainer = User::find($newTrainerId);
                    if ($newTrainer) {
                        $calendarService->addAttendee($calendarId, $session->google_event_id, $newTrainer->email, $newTrainer->name);
                    }
                }

                $calendarOk = true;
            } catch (\Throwable $e) {
                Log::warning('Google Calendar Event konnte nicht aktualisiert werden.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($calendarOk) {
            return back()->with('success', 'Termin aktualisiert und Google Calendar synchronisiert!');
        }

        $message = 'Termin aktualisiert.';
        if ($session->google_event_id) {
            return back()->with('success', $message)->with('warning', 'Google Calendar Synchronisation fehlgeschlagen.');
        }

        return back()->with('success', $message);
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
     * Module IDs where the current user is accountable trainer OR assigned via module_trainer pivot.
     */
    private function ownTrainerModuleIds($user): array
    {
        $accountableIds = Module::where('accountable_type', 'user')
            ->where('accountable_user_id', $user->id)
            ->pluck('id');

        $pivotIds = $user->trainableModules()->pluck('modules.id');

        return $accountableIds->merge($pivotIds)->unique()->values()->toArray();
    }
}
