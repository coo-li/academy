<?php

namespace App\Console\Commands;

use App\Models\BudgetSyncProject;
use App\Services\TrafficDesignBudgetService;
use Illuminate\Console\Command;

class SyncTrafficDesignBudgetsCommand extends Command
{
    protected $signature = 'academy:sync-budgets
                            {--year= : Das Jahr für den Sync (Standard: aktuelles Jahr)}
                            {--dry-run : Nur simulieren, keine Änderungen vornehmen}
                            {--test : Nur API-Verbindung testen}
                            {--capacities : Personenbezogene Soll/Ist-Stunden synchronisieren}
                            {--tm-api : TM API mit monatlicher Granularität verwenden (empfohlen)}';

    protected $description = 'Synchronisiere Budgets aus dem TrafficDesign Budget Tracker';

    public function handle(TrafficDesignBudgetService $service): int
    {
        $this->info('🔄 TrafficDesign Budget Sync');
        $this->newLine();

        // Check configuration
        if (!$service->isConfigured()) {
            $this->error('TrafficDesign Budget Service ist nicht konfiguriert.');
            $this->newLine();
            $this->line('Bitte folgende Umgebungsvariablen in der .env setzen:');
            $this->line('  TRAFFICDESIGN_SYNC_ENABLED=true');
            $this->line('  TRAFFICDESIGN_BUDGET_API_URL=https://budgets.trafficdesign.de/api');
            $this->line('  TRAFFICDESIGN_TM_API_URL=https://tm-test.trafficdesign.de/api');
            $this->line('  TRAFFICDESIGN_TM_API_KEY=dein_api_key');

            return self::FAILURE;
        }

        // Test mode - only check connectivity
        if ($this->option('test')) {
            return $this->testConnection($service);
        }

        // Check if projects are configured
        $configuredProjects = BudgetSyncProject::active()->count();
        if ($configuredProjects === 0) {
            $this->error('Keine Projekte konfiguriert.');
            $this->newLine();
            $this->line('Bitte Projekte in Admin > Budget-Sync Projekte hinzufügen.');
            $this->line('URL: /admin/dashboard/budget-sync-projects');
            
            return self::FAILURE;
        }

        $this->info("📋 {$configuredProjects} Projekte konfiguriert");
        
        $year = (int) ($this->option('year') ?: date('Y'));
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY-RUN Modus – keine Änderungen werden gespeichert.');
            $this->newLine();
        }

        $this->info("Synchronisiere Budgets für Jahr: {$year}");
        $this->newLine();

        // Decide which sync method to use
        $useTmApi = $this->option('tm-api');
        
        if ($useTmApi) {
            // TM API with monthly granularity (recommended)
            $this->output->write('Hole Budgets von TM API (monatliche Daten)... ');
            
            $results = $service->syncProjectBudgetsFromTM($year, $dryRun);

            $this->info('Fertig!');
            $this->newLine();

            // Display results
            if (empty($results['errors'])) {
                $this->info('✅ TM API Sync erfolgreich abgeschlossen!');
            } else {
                $this->warn('⚠️  Sync mit Fehlern abgeschlossen.');
            }

            $this->newLine();
            $this->table(
                ['Metrik', 'Wert'],
                [
                    ['Jahr', $results['year']],
                    ['Projekte verarbeitet', $results['projects_processed']],
                    ['Einträge erstellt', $results['entries_created']],
                    ['Einträge aktualisiert', $results['entries_updated']],
                    ['Übersprungen (Dry-Run)', $results['entries_skipped']],
                    ['User nicht gefunden', $results['user_not_found']],
                ]
            );
        } else {
            // Original Budget Tracker API sync
            $this->output->write('Hole Budgets von der API... ');
            
            $results = $service->syncBudgets($year, $dryRun);

            $this->info('Fertig!');
            $this->newLine();

            // Display results
            if (empty($results['errors'])) {
                $this->info('✅ Sync erfolgreich abgeschlossen!');
            } else {
                $this->warn('⚠️  Sync mit Fehlern abgeschlossen.');
            }

            $this->newLine();
            $this->table(
                ['Metrik', 'Wert'],
                [
                    ['Jahr', $results['year']],
                    ['Einträge verarbeitet', $results['processed']],
                    ['Neu erstellt', $results['created']],
                    ['Aktualisiert', $results['updated']],
                    ['Übersprungen (Dry-Run)', $results['skipped']],
                    ['Gefiltert (nicht konfiguriert)', $results['filtered_out']],
                ]
            );

            // Show user mapping errors
            if (!empty($results['user_mapping_errors'])) {
                $this->newLine();
                $this->warn('⚠️  User-Mapping Probleme:');
                $uniqueErrors = array_unique($results['user_mapping_errors']);
                foreach (array_slice($uniqueErrors, 0, 10) as $error) {
                    $this->line("  - {$error}");
                }
                if (count($uniqueErrors) > 10) {
                    $this->line("  ... und " . (count($uniqueErrors) - 10) . " weitere");
                }
            }
        }

        // Show errors
        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('Fehler:');
            foreach ($results['errors'] as $error) {
                $this->line("  - {$error}");
            }
            return self::FAILURE;
        }

        // Capacity sync if requested
        if ($this->option('capacities')) {
            return $this->syncCapacities($service, $year, $dryRun);
        }

        return self::SUCCESS;
    }

    /**
     * Sync personal capacities (planned/actual hours).
     */
    protected function syncCapacities(TrafficDesignBudgetService $service, int $year, bool $dryRun): int
    {
        $this->newLine();
        $this->info('🕐 Synchronisiere personenbezogene Kapazitäten (Soll/Ist-Stunden)...');
        $this->newLine();

        $results = $service->syncPersonCapacities($year, $dryRun);

        // Display results
        if (empty($results['errors'])) {
            $this->info('✅ Kapazitäts-Sync erfolgreich!');
        } else {
            $this->warn('⚠️  Sync mit Fehlern abgeschlossen.');
        }

        $this->newLine();
        $this->table(
            ['Metrik', 'Wert'],
            [
                ['Jahr', $results['year']],
                ['User verarbeitet', $results['users_processed']],
                ['Einträge erstellt', $results['entries_created']],
                ['Einträge aktualisiert', $results['entries_updated']],
                ['Übersprungen (Dry-Run)', $results['entries_skipped']],
            ]
        );

        // Show errors
        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('Fehler:');
            foreach (array_slice($results['errors'], 0, 10) as $error) {
                $this->line("  - {$error}");
            }
            if (count($results['errors']) > 10) {
                $this->line("  ... und " . (count($results['errors']) - 10) . " weitere");
            }
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Test API connectivity.
     */
    protected function testConnection(TrafficDesignBudgetService $service): int
    {
        $this->info('Teste API-Verbindungen...');
        $this->newLine();

        $results = $service->testConnection();
        $allSuccess = true;

        // Configured projects
        $this->line("Konfigurierte Projekte: {$results['configured_projects']}");
        $this->newLine();

        // Budget Tracker API
        $this->output->write('Budget Tracker API: ');
        if ($results['budget_api']['success']) {
            $this->info('✅ ' . $results['budget_api']['message']);
        } else {
            $this->error('❌ ' . $results['budget_api']['message']);
            $allSuccess = false;
        }

        // TM API
        $this->output->write('TM API:             ');
        if ($results['tm_api']['success']) {
            $this->info('✅ ' . $results['tm_api']['message']);
        } else {
            $this->error('❌ ' . $results['tm_api']['message']);
            $allSuccess = false;
        }

        $this->newLine();

        if ($allSuccess) {
            if ($results['configured_projects'] > 0) {
                $this->info('Alle APIs erreichbar. Du kannst jetzt den Sync starten:');
                $this->line('  php artisan academy:sync-budgets --dry-run');
            } else {
                $this->warn('APIs erreichbar, aber keine Projekte konfiguriert.');
                $this->line('Bitte Projekte in Admin > Budget-Sync Projekte hinzufügen.');
            }
        } else {
            $this->warn('Einige APIs sind nicht erreichbar. Bitte prüfe die Konfiguration.');
        }

        return $allSuccess ? self::SUCCESS : self::FAILURE;
    }
}
