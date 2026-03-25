<?php

namespace App\Console\Commands;

use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;

class TestCalendarCommand extends Command
{
    protected $signature = 'academy:test-calendar';

    protected $description = 'Testet die Google Calendar Verbindung und Konfiguration';

    public function handle(GoogleCalendarService $calendar): int
    {
        $this->info('Google Calendar Verbindungstest');
        $this->newLine();

        $credentialsPath = config('google.credentials_path', storage_path('app/google-auth.json'));
        $calendarId = config('services.google.calendar_id', 'primary');
        $impersonate = config('services.google.calendar_impersonate');

        $this->table(['Einstellung', 'Wert'], [
            ['Credentials', file_exists($credentialsPath) ? 'OK (' . basename($credentialsPath) . ')' : 'FEHLT: ' . $credentialsPath],
            ['Calendar ID', $calendarId],
            ['Impersonate', $impersonate ?: '(nicht gesetzt)'],
        ]);

        $this->newLine();

        if (! $calendar->isConfigured()) {
            $this->error('GoogleCalendarService ist nicht konfiguriert. Credentials-Datei fehlt?');
            return self::FAILURE;
        }

        $this->info('Service ist konfiguriert. Teste API-Zugriff...');

        try {
            $events = $calendar->getUpcomingEvents($calendarId, 1);
            $this->info('API-Zugriff erfolgreich!');
            $this->line('  Anzahl kommender Events: ' . count($events));

            if (count($events) > 0) {
                $event = $events[0];
                $this->line('  Naechstes Event: ' . $event->getSummary());
            }

            $this->newLine();
            $this->info('Alles OK – Google Calendar Integration funktioniert.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('API-Zugriff fehlgeschlagen!');
            $this->error($e->getMessage());
            $this->newLine();
            $this->warn('Moegliche Ursachen:');
            $this->line('  1. Domain-Wide Delegation nicht aktiviert fuer den Service Account');
            $this->line('  2. Client-ID nicht im Google Workspace Admin autorisiert');
            $this->line('  3. Impersonate-User (' . $impersonate . ') existiert nicht oder hat keinen Kalender-Zugriff');
            $this->line('  4. OAuth-Scopes fehlen: calendar, calendar.events');

            return self::FAILURE;
        }
    }
}
