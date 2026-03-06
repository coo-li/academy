<?php

namespace App\Console\Commands;

use App\Services\PersonioService;
use Illuminate\Console\Command;

class SyncPersonioCommand extends Command
{
    protected $signature = 'academy:sync-personio
                            {--dry-run : Nur simulieren, keine Änderungen vornehmen}';

    protected $description = 'Synchronisiere alle aktiven Mitarbeiter aus Personio mit der Academy';

    public function handle(PersonioService $personio): int
    {
        $this->info('🔄 Starte Personio-Sync...');
        $this->newLine();

        if (! $personio->isConfigured()) {
            $this->error('Personio API ist nicht konfiguriert.');
            $this->line('Bitte PERSONIO_CLIENT_ID und PERSONIO_CLIENT_SECRET in der .env setzen.');

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->warn('DRY-RUN Modus – keine Änderungen werden gespeichert.');
            $this->newLine();

            $employees = $personio->getActiveEmployees();

            if ($employees === null) {
                $this->error('Fehler beim Abrufen der Mitarbeiter.');
                return self::FAILURE;
            }

            $this->info("Gefundene aktive Mitarbeiter: " . count($employees));
            $this->newLine();

            $this->table(
                ['Personio-ID', 'Name', 'E-Mail', 'Position', 'Abteilung'],
                collect($employees)->map(fn ($e) => [
                    $e['personio_id'],
                    trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')),
                    $e['email'],
                    $e['position'] ?? '–',
                    $e['department'] ?? '–',
                ])->toArray()
            );

            return self::SUCCESS;
        }

        $log = $personio->syncEmployees();

        $this->newLine();

        if ($log->isSuccess()) {
            $this->info('✅ Sync erfolgreich abgeschlossen!');
        } else {
            $this->warn("⚠️  Sync mit Status: {$log->status}");
        }

        $this->newLine();
        $this->table(
            ['Metrik', 'Wert'],
            [
                ['Mitarbeiter abgerufen', $log->employees_fetched],
                ['Neue User erstellt', $log->users_created],
                ['User aktualisiert', $log->users_updated],
                ['Übersprungen', $log->users_skipped],
                ['Dauer', $log->duration() ?? '–'],
            ]
        );

        if ($log->error_message) {
            $this->newLine();
            $this->error('Fehler:');
            $this->line($log->error_message);
        }

        $unmapped = PersonioService::getUsersWithoutCareerPath();
        if ($unmapped > 0) {
            $this->newLine();
            $this->warn("⚠️  {$unmapped} Mitarbeiter haben noch keinen zugewiesenen Karrierepfad.");
            $this->line('Bitte die Karriere-Matrix im Admin-Bereich prüfen.');
        }

        return $log->isSuccess() ? self::SUCCESS : self::FAILURE;
    }
}
