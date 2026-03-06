<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;
use Google\Service\Calendar\Event as GoogleCalendarEvent;
use Google\Service\Calendar\EventAttendee;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService
{
    protected GoogleClient $client;
    protected GoogleCalendar $calendarService;

    protected bool $configured = false;

    public function __construct()
    {
        $this->client = new GoogleClient();

        $credentialsPath = config('google.credentials_path', storage_path('app/google-auth.json'));

        if (! file_exists($credentialsPath)) {
            Log::warning('Google Calendar: Credentials file not found.', ['path' => $credentialsPath]);
            return;
        }

        $this->client->setAuthConfig($credentialsPath);
        $this->client->addScope(GoogleCalendar::CALENDAR);
        $this->client->addScope(GoogleCalendar::CALENDAR_EVENTS);

        $impersonate = config('services.google.calendar_impersonate');
        if ($impersonate) {
            $this->client->setSubject($impersonate);
        }

        $this->calendarService = new GoogleCalendar($this->client);
        $this->configured = true;
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    /**
     * Retrieve upcoming events from a calendar.
     */
    public function getUpcomingEvents(string $calendarId, int $maxResults = 10): array
    {
        if (! $this->configured) {
            return [];
        }

        $events = $this->calendarService->events->listEvents($calendarId, [
            'maxResults' => $maxResults,
            'orderBy' => 'startTime',
            'singleEvents' => true,
            'timeMin' => now()->toRfc3339String(),
        ]);

        return $events->getItems();
    }

    /**
     * Create an event on a calendar (e.g. training session, exam deadline).
     */
    public function createEvent(
        string $calendarId,
        string $title,
        Carbon $start,
        Carbon $end,
        ?string $description = null,
        ?string $location = null,
    ): ?GoogleCalendarEvent {
        if (! $this->configured) {
            return null;
        }

        $event = new GoogleCalendarEvent([
            'summary' => $title,
            'description' => $description,
            'location' => $location,
            'start' => [
                'dateTime' => $start->toRfc3339String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ],
            'end' => [
                'dateTime' => $end->toRfc3339String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ],
        ]);

        return $this->calendarService->events->insert($calendarId, $event);
    }

    /**
     * Update an existing calendar event.
     */
    public function updateEvent(
        string $calendarId,
        string $eventId,
        array $attributes,
    ): ?GoogleCalendarEvent {
        if (! $this->configured) {
            return null;
        }

        $event = $this->calendarService->events->get($calendarId, $eventId);

        if (isset($attributes['title'])) {
            $event->setSummary($attributes['title']);
        }

        if (isset($attributes['description'])) {
            $event->setDescription($attributes['description']);
        }

        if (isset($attributes['start'])) {
            $event->setStart(new EventDateTime([
                'dateTime' => $attributes['start']->toRfc3339String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ]));
        }

        if (isset($attributes['end'])) {
            $event->setEnd(new EventDateTime([
                'dateTime' => $attributes['end']->toRfc3339String(),
                'timeZone' => config('app.timezone', 'Europe/Berlin'),
            ]));
        }

        return $this->calendarService->events->update($calendarId, $eventId, $event);
    }

    /**
     * Delete a calendar event.
     */
    public function deleteEvent(string $calendarId, string $eventId): void
    {
        if (! $this->configured) {
            return;
        }

        $this->calendarService->events->delete($calendarId, $eventId);
    }

    /**
     * Retrieve a single event by its ID.
     */
    public function getEvent(string $calendarId, string $eventId): ?GoogleCalendarEvent
    {
        if (! $this->configured) {
            return null;
        }

        try {
            return $this->calendarService->events->get($calendarId, $eventId);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar: Could not fetch event.', [
                'eventId' => $eventId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Add an attendee (by email) to an existing calendar event.
     * If the event has a google_event_id stored on the training session,
     * the user is added as participant.
     */
    public function addAttendee(string $calendarId, string $eventId, string $email, ?string $displayName = null): ?GoogleCalendarEvent
    {
        if (! $this->configured) {
            return null;
        }

        try {
            $event = $this->calendarService->events->get($calendarId, $eventId);

            $attendees = $event->getAttendees() ?? [];

            foreach ($attendees as $attendee) {
                if ($attendee->getEmail() === $email) {
                    return $event;
                }
            }

            $newAttendee = new EventAttendee([
                'email' => $email,
                'displayName' => $displayName,
                'responseStatus' => 'accepted',
            ]);
            $attendees[] = $newAttendee;
            $event->setAttendees($attendees);

            return $this->calendarService->events->update($calendarId, $eventId, $event, [
                'sendUpdates' => 'none',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar: Could not add attendee.', [
                'eventId' => $eventId,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Remove an attendee from an existing calendar event.
     */
    public function removeAttendee(string $calendarId, string $eventId, string $email): ?GoogleCalendarEvent
    {
        if (! $this->configured) {
            return null;
        }

        try {
            $event = $this->calendarService->events->get($calendarId, $eventId);
            $attendees = $event->getAttendees() ?? [];
            $filtered = array_filter($attendees, fn ($a) => $a->getEmail() !== $email);

            $event->setAttendees(array_values($filtered));

            return $this->calendarService->events->update($calendarId, $eventId, $event, [
                'sendUpdates' => 'none',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Google Calendar: Could not remove attendee.', [
                'eventId' => $eventId,
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
