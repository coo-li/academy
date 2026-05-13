<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTrainingSessionRequest;
use App\Http\Requests\UpdateTrainingSessionRequest;
use App\Models\Enrollment;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\TrainingSessionSeries;
use App\Models\User;
use App\Notifications\EnrollmentStatusChanged;
use App\Services\EnrollmentService;
use App\Services\GoogleCalendarService;
use App\Services\RecurringSessionService;
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

        $modulesQuery = Module::with('careerLevel.careerPath', 'accountableUser', 'trainers', 'method');
        if ($showAll) {
            $modulesQuery->orderBy('title');
        } else {
            $modulesQuery->whereIn('id', $ownModuleIds)->orderBy('title');
        }
        $modules = $modulesQuery->get();

        $baseQuery = TrainingSession::with(['module.method', 'module.accountableUser', 'trainer', 'enrollments.user', 'series'])
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

        $schedulableModules = $modules->filter(fn ($m) => ($m->method?->scheduling_type ?? 'scheduled') !== 'self_study');

        $pendingRequests = Enrollment::with(['user', 'module.method'])
            ->whereIn('module_id', $displayModuleIds)
            ->where('status', 'requested')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('trainer.termine', compact('modules', 'schedulableModules', 'upcomingSessions', 'pastSessions', 'resources', 'showAll', 'moduleTrainers', 'pendingRequests'));
    }

    public function store(StoreTrainingSessionRequest $request)
    {
        $location = $this->resolveLocation($request);
        $trainerId = $request->input('trainer_id') ?: Auth::id();
        $isRecurring = $request->boolean('is_recurring');

        if ($isRecurring) {
            return $this->storeRecurringSeries($request, $location, $trainerId);
        }

        return $this->storeSingleSession($request, $location, $trainerId);
    }

    private function storeSingleSession(StoreTrainingSessionRequest $request, ?string $location, int $trainerId)
    {
        $session = TrainingSession::create([
            'module_id' => $request->module_id,
            'trainer_id' => $trainerId,
            'start_at' => $request->startAt(),
            'end_at' => $request->endAt(),
            'location' => $location,
            'max_participants' => $request->max_participants,
        ]);

        $calendarOk = $this->syncNewSessionToCalendar($session, $request);

        if ($calendarOk) {
            return back()->with('success', 'Workshop-Termin erstellt und Google Calendar synchronisiert!');
        }

        return back()
            ->with('success', 'Workshop-Termin erstellt.')
            ->with('warning', 'Google Calendar Synchronisation fehlgeschlagen – der Termin kann nachträglich synchronisiert werden.');
    }

    private function storeRecurringSeries(StoreTrainingSessionRequest $request, ?string $location, int $trainerId)
    {
        $daysOfWeek = $request->input('days_of_week', []);
        if (is_array($daysOfWeek)) {
            $daysOfWeek = array_map('intval', $daysOfWeek);
        }

        $seriesData = [
            'module_id' => $request->module_id,
            'trainer_id' => $trainerId,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('series_end_date') ?: null,
            'start_time' => $request->input('start_time'),
            'end_time' => $request->input('end_time'),
            'frequency' => $request->input('frequency', 'weekly'),
            'frequency_interval' => (int) $request->input('frequency_interval', 1),
            'days_of_week' => $daysOfWeek,
            'location' => $location,
            'max_participants' => $request->max_participants,
        ];

        $calendarOptions = [
            'calendar_description' => $request->input('calendar_description'),
            'google_meet' => $request->boolean('google_meet'),
            'resource_email' => $request->input('resource_email'),
        ];

        $service = app(RecurringSessionService::class);
        $result = $service->createSeries($seriesData, $calendarOptions);

        $created = $result['created'];
        $synced = $result['synced'];
        $weekendDates = $result['weekend_dates'];

        $successMsg = "Terminserie erstellt – {$created} Termine wurden generiert";
        if ($synced > 0) {
            $successMsg .= ", {$synced} davon mit Google Calendar synchronisiert";
        }
        $successMsg .= '.';

        $warnings = [];

        if ($synced < $created && $synced > 0) {
            $failed = $created - $synced;
            $warnings[] = "Kalender-Sync für {$failed} Termine fehlgeschlagen – diese können nachträglich synchronisiert werden.";
        } elseif ($synced === 0 && $created > 0) {
            $warnings[] = 'Google Calendar Synchronisation fehlgeschlagen – Termine können nachträglich synchronisiert werden.';
        }

        if ($weekendDates->isNotEmpty()) {
            $dateList = $weekendDates->map(fn ($d) => $d->format('d.m.'))->implode(', ');
            $count = $weekendDates->count();
            $warnings[] = "{$count} Termine fallen auf ein Wochenende (Sa/So) – bitte manuell prüfen: {$dateList}";
        }

        $redirect = back()->with('success', $successMsg);

        if (! empty($warnings)) {
            $redirect = $redirect->with('warning', implode(' | ', $warnings));
        }

        return $redirect;
    }

    private function syncNewSessionToCalendar(TrainingSession $session, StoreTrainingSessionRequest $request): bool
    {
        $module = Module::find($session->module_id);
        if (! $module) {
            return false;
        }

        $service = app(RecurringSessionService::class);

        return $service->syncSessionToCalendar($session, $module, [
            'calendar_description' => $request->input('calendar_description'),
            'google_meet' => (bool) $request->input('google_meet', false),
            'resource_email' => $request->input('resource_email'),
        ]);
    }

    private function resolveLocation(Request $request): ?string
    {
        $location = $request->input('location');
        $resourceEmail = $request->input('resource_email');

        if ($resourceEmail && ! $location) {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $resources = $calendarService->listResources();
                foreach ($resources as $res) {
                    if ($res['email'] === $resourceEmail) {
                        return $res['name'];
                    }
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        return $location;
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
        $location = $this->resolveLocation($request);

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

    public function destroy(Request $request, TrainingSession $session)
    {
        $deleteScope = $request->input('delete_scope', 'single');
        $service = app(RecurringSessionService::class);

        if ($session->series_id && $deleteScope === 'future') {
            $count = $service->deleteFutureSessions($session);
            return back()->with('success', "{$count} Termine (dieser und alle zukünftigen) gelöscht.");
        }

        if ($session->series_id && $deleteScope === 'all') {
            $series = $session->series;
            if ($series) {
                $count = $service->deleteEntireSeries($series);
                return back()->with('success', "Gesamte Serie gelöscht ({$count} Termine).");
            }
        }

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
     * Create a session for a pending request and assign the participant.
     */
    public function assignRequest(Request $request, Enrollment $enrollment)
    {
        if ($enrollment->status !== 'requested') {
            return back()->with('error', 'Diese Anfrage wurde bereits bearbeitet.');
        }

        $request->validate([
            'start_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:500'],
        ]);

        $startAt = \Illuminate\Support\Carbon::parse($request->start_date . ' ' . $request->start_time);
        $endAt = \Illuminate\Support\Carbon::parse($request->start_date . ' ' . $request->end_time);

        $session = TrainingSession::create([
            'module_id' => $enrollment->module_id,
            'trainer_id' => Auth::id(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'location' => $request->location,
            'max_participants' => 2,
        ]);

        $enrollmentService = app(EnrollmentService::class);
        $enrollmentService->assignSession($enrollment, $session);

        $enrollment->load('module');
        $enrollment->user->notify(new EnrollmentStatusChanged($enrollment));

        return back()->with('success', "Termin erstellt und {$enrollment->user->name} zugewiesen.");
    }

    /**
     * Decline a pending session request.
     */
    public function declineRequest(Enrollment $enrollment)
    {
        if ($enrollment->status !== 'requested') {
            return back()->with('error', 'Diese Anfrage wurde bereits bearbeitet.');
        }

        $enrollment->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $enrollment->load('module');
        $enrollment->user->notify(new EnrollmentStatusChanged($enrollment));

        return back()->with('success', "Terminanfrage von {$enrollment->user->name} wurde abgelehnt.");
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
