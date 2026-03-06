<?php

namespace App\Console\Commands;

use App\Models\Enrollment;
use App\Models\TrainingSession;
use App\Services\AsanaService;
use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncCalendarCancellationsCommand extends Command
{
    protected $signature = 'academy:sync-cancellations
                            {--dry-run : Nur simulieren, keine Änderungen vornehmen}';

    protected $description = 'Gleicht Google Calendar Absagen mit Academy-Buchungen ab';

    public function handle(GoogleCalendarService $calendar, AsanaService $asana): int
    {
        $this->info('Starte Google Calendar Absagen-Sync...');
        $this->newLine();

        if (! $calendar->isConfigured()) {
            $this->error('Google Calendar ist nicht konfiguriert.');
            return self::FAILURE;
        }

        $calendarId = config('services.google.calendar_id', 'primary');

        $sessions = TrainingSession::whereNotNull('google_event_id')
            ->where('start_at', '>', now())
            ->with(['enrollments' => fn ($q) => $q->whereIn('status', ['enrolled', 'in_progress'])->with('user')])
            ->get();

        if ($sessions->isEmpty()) {
            $this->info('Keine zukünftigen Sessions mit Google Calendar Events gefunden.');
            return self::SUCCESS;
        }

        $this->info("Prüfe {$sessions->count()} Sessions...");
        $cancelled = 0;

        foreach ($sessions as $session) {
            if ($session->enrollments->isEmpty()) {
                continue;
            }

            $event = $calendar->getEvent($calendarId, $session->google_event_id);
            if (! $event) {
                $this->warn("  Event {$session->google_event_id} nicht abrufbar, überspringe.");
                continue;
            }

            $attendees = collect($event->getAttendees() ?? []);
            $attendeeMap = $attendees->keyBy(fn ($a) => strtolower($a->getEmail()));

            foreach ($session->enrollments as $enrollment) {
                $email = strtolower($enrollment->user->email);
                $attendee = $attendeeMap->get($email);

                $shouldCancel = ! $attendee || $attendee->getResponseStatus() === 'declined';

                if (! $shouldCancel) {
                    continue;
                }

                $reason = ! $attendee ? 'nicht mehr als Teilnehmer gelistet' : 'hat im Google Calendar abgesagt';

                if ($this->option('dry-run')) {
                    $this->warn("  [DRY-RUN] Würde stornieren: {$enrollment->user->name} ({$email}) – {$reason}");
                    $cancelled++;
                    continue;
                }

                $enrollment->update([
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                ]);

                if ($enrollment->asana_task_gid) {
                    try {
                        $asana->addComment(
                            $enrollment->asana_task_gid,
                            "Buchung automatisch storniert – {$reason} (Google Calendar Sync).",
                        );
                        $asana->completeTask($enrollment->asana_task_gid);
                    } catch (\Throwable $e) {
                        Log::warning('Asana: Fehler beim Sync-Cancel.', ['error' => $e->getMessage()]);
                    }
                }

                Log::info('Calendar Sync: Enrollment storniert.', [
                    'user' => $enrollment->user->name,
                    'email' => $email,
                    'module' => $enrollment->module_id,
                    'session' => $session->id,
                    'reason' => $reason,
                ]);

                $this->line("  Storniert: {$enrollment->user->name} ({$email}) – {$reason}");
                $cancelled++;
            }
        }

        $this->newLine();
        $prefix = $this->option('dry-run') ? '[DRY-RUN] ' : '';
        $this->info("{$prefix}Fertig. {$cancelled} Buchung(en) storniert.");

        return self::SUCCESS;
    }
}
