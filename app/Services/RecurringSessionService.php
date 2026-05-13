<?php

namespace App\Services;

use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\TrainingSessionSeries;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class RecurringSessionService
{
    /**
     * Default horizon: generate sessions up to 6 months ahead.
     */
    private const HORIZON_MONTHS = 6;

    /**
     * @param  array  $calendarOptions  Optional: ['calendar_description' => ?string, 'google_meet' => bool, 'resource_email' => ?string]
     * @return array{series: TrainingSessionSeries, created: int, synced: int, weekend_dates: Collection}
     */
    public function createSeries(array $data, array $calendarOptions = []): array
    {
        $series = TrainingSessionSeries::create($data);

        $result = $this->generateSessions($series, null, $calendarOptions);

        return [
            'series' => $series,
            'created' => $result['created']->count(),
            'synced' => $result['synced'],
            'weekend_dates' => $result['weekend_dates'],
        ];
    }

    /**
     * Generate individual TrainingSession records for a series,
     * from the series start_date up to end_date or 6 months ahead.
     * Optionally syncs each session to Google Calendar.
     *
     * @param  array  $calendarOptions  ['calendar_description' => ?string, 'google_meet' => bool, 'resource_email' => ?string]
     * @return array{created: Collection, synced: int, weekend_dates: Collection}
     */
    public function generateSessions(TrainingSessionSeries $series, ?Carbon $fromDate = null, array $calendarOptions = []): array
    {
        $from = $fromDate ?? $series->start_date->copy();
        $horizon = $series->end_date
            ? $series->end_date->copy()->endOfDay()
            : Carbon::today()->addMonths(self::HORIZON_MONTHS)->endOfDay();

        if ($series->end_date && $horizon->gt($series->end_date->endOfDay())) {
            $horizon = $series->end_date->copy()->endOfDay();
        }

        $existingDates = $series->sessions()
            ->pluck('start_at')
            ->map(fn ($dt) => $dt->format('Y-m-d'))
            ->toArray();

        $dates = $this->computeOccurrences($series, $from, $horizon);

        $created = collect();
        $weekendDates = collect();
        $syncedCount = 0;

        $module = $series->module;

        foreach ($dates as $date) {
            if (in_array($date->format('Y-m-d'), $existingDates)) {
                continue;
            }

            if ($date->isWeekend()) {
                $weekendDates->push($date->copy());
            }

            $startAt = $date->copy()->setTimeFromTimeString($series->start_time);
            $endAt = $date->copy()->setTimeFromTimeString($series->end_time);

            $session = TrainingSession::create([
                'module_id' => $series->module_id,
                'series_id' => $series->id,
                'trainer_id' => $series->trainer_id,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'location' => $series->location,
                'max_participants' => $series->max_participants,
            ]);

            if (! empty($calendarOptions) && $module) {
                if ($this->syncSessionToCalendar($session, $module, $calendarOptions)) {
                    $syncedCount++;
                }
            }

            $created->push($session);
        }

        return [
            'created' => $created,
            'synced' => $syncedCount,
            'weekend_dates' => $weekendDates,
        ];
    }

    /**
     * Sync a single session to Google Calendar. Returns true on success.
     */
    public function syncSessionToCalendar(TrainingSession $session, Module $module, array $options = []): bool
    {
        try {
            $calendarService = app(GoogleCalendarService::class);
            if (! $calendarService->isConfigured()) {
                return false;
            }

            $calendarId = config('services.google.calendar_id', 'primary');

            $description = trim($options['calendar_description'] ?? '') ?: trim($module->calendar_description ?? '');
            $eventDescription = "Academy Workshop – {$module->title}";
            if ($description) {
                $eventDescription .= "\n\n{$description}";
            }

            $withMeet = (bool) ($options['google_meet'] ?? false);
            $resourceEmail = $options['resource_email'] ?? null;

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
                return true;
            }
        } catch (\Throwable $e) {
            Log::warning('Google Calendar Event konnte nicht erstellt werden (Serientermin).', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Compute all occurrence dates within the given range based on frequency rules.
     *
     * @return Collection<Carbon>
     */
    private function computeOccurrences(TrainingSessionSeries $series, Carbon $from, Carbon $horizon): Collection
    {
        $dates = collect();
        $interval = max(1, $series->frequency_interval);

        return match ($series->frequency) {
            'daily' => $this->computeDaily($from, $horizon, $interval),
            'weekly' => $this->computeWeekly($from, $horizon, $interval, $series->days_of_week ?? []),
            'monthly' => $this->computeMonthly($from, $horizon, $interval),
            default => $dates,
        };
    }

    private function computeDaily(Carbon $from, Carbon $horizon, int $interval): Collection
    {
        $dates = collect();
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($horizon)) {
            if ($cursor->gte($from)) {
                $dates->push($cursor->copy());
            }
            $cursor->addDays($interval);
        }

        return $dates;
    }

    private function computeWeekly(Carbon $from, Carbon $horizon, int $interval, array $daysOfWeek): Collection
    {
        $dates = collect();

        if (empty($daysOfWeek)) {
            $daysOfWeek = [$from->dayOfWeekIso];
        }

        sort($daysOfWeek);

        $weekStart = $from->copy()->startOfWeek(Carbon::MONDAY);
        $weekNumber = 0;

        while ($weekStart->lte($horizon)) {
            if ($weekNumber % $interval === 0) {
                foreach ($daysOfWeek as $dayIso) {
                    $date = $weekStart->copy()->startOfWeek(Carbon::MONDAY)->addDays($dayIso - 1);
                    if ($date->gte($from) && $date->lte($horizon)) {
                        $dates->push($date);
                    }
                }
            }
            $weekStart->addWeek();
            $weekNumber++;
        }

        return $dates;
    }

    private function computeMonthly(Carbon $from, Carbon $horizon, int $interval): Collection
    {
        $dates = collect();
        $dayOfMonth = $from->day;
        $cursor = $from->copy()->startOfMonth();

        while ($cursor->lte($horizon)) {
            $target = $cursor->copy()->day(min($dayOfMonth, $cursor->daysInMonth));
            if ($target->gte($from) && $target->lte($horizon)) {
                $dates->push($target);
            }
            $cursor->addMonths($interval);
        }

        return $dates;
    }

    /**
     * Delete a single session from a series (detach or hard-delete).
     */
    public function deleteSingleSession(TrainingSession $session): void
    {
        $session->delete();
    }

    /**
     * Delete this session and all future sessions in the same series.
     * If no sessions remain, also delete the series itself.
     */
    public function deleteFutureSessions(TrainingSession $session): int
    {
        $series = $session->series;

        if (! $series) {
            $session->delete();
            return 1;
        }

        $futureSessions = $series->sessions()
            ->where('start_at', '>=', $session->start_at)
            ->get();

        $count = $futureSessions->count();

        foreach ($futureSessions as $futureSession) {
            $this->deleteSessionWithCalendar($futureSession);
        }

        if ($series->sessions()->count() === 0) {
            $series->delete();
        } else {
            $lastSession = $series->sessions()->orderByDesc('start_at')->first();
            if ($lastSession) {
                $series->update(['end_date' => $lastSession->start_at->toDateString()]);
            }
        }

        return $count;
    }

    /**
     * Delete all sessions in a series and the series itself.
     */
    public function deleteEntireSeries(TrainingSessionSeries $series): int
    {
        $sessions = $series->sessions()->get();
        $count = $sessions->count();

        foreach ($sessions as $session) {
            $this->deleteSessionWithCalendar($session);
        }

        $series->delete();

        return $count;
    }

    /**
     * Delete a session, cleaning up its Google Calendar event if present.
     */
    private function deleteSessionWithCalendar(TrainingSession $session): void
    {
        if ($session->google_event_id) {
            try {
                $calendarService = app(GoogleCalendarService::class);
                $calendarId = config('services.google.calendar_id', 'primary');
                $calendarService->deleteEvent($calendarId, $session->google_event_id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Google Calendar Event konnte nicht gelöscht werden.', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $session->delete();
    }

    /**
     * Extend all open-ended series by generating new sessions up to the horizon.
     * Called e.g. via a scheduled command monthly.
     */
    public function extendAllSeries(): int
    {
        $count = 0;
        $series = TrainingSessionSeries::whereNull('end_date')->get();

        foreach ($series as $s) {
            $lastSession = $s->sessions()->orderByDesc('start_at')->first();
            $fromDate = $lastSession
                ? $lastSession->start_at->copy()->addDay()
                : $s->start_date->copy();

            $result = $this->generateSessions($s, $fromDate);
            $count += $result['created']->count();
        }

        return $count;
    }
}
