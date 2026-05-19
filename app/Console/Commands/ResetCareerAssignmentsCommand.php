<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PersonioService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetCareerAssignmentsCommand extends Command
{
    protected $signature = 'academy:reset-career-assignments
                            {--force : Reset ohne Bestätigung durchführen}
                            {--skip-sync : Personio-Sync nach dem Reset überspringen}';

    protected $description = 'Setzt alle Karrierestufen-Zuweisungen zurück und führt Personio-Sync erneut aus';

    public function handle(PersonioService $personio): int
    {
        $this->info('🔄 Reset der Karrierestufen- und Modul-Zuweisungen');
        $this->newLine();

        $pivotCount = DB::table('career_level_user')->count();
        $usersWithLevel = User::whereNotNull('career_level_id')->count();
        $moduleAssignments = DB::table('module_assignments')->count();

        $this->table(
            ['Metrik', 'Wert'],
            [
                ['Einträge in career_level_user', $pivotCount],
                ['User mit career_level_id', $usersWithLevel],
                ['Direkte Modul-Zuweisungen', $moduleAssignments],
            ]
        );

        if ($pivotCount === 0 && $usersWithLevel === 0 && $moduleAssignments === 0) {
            $this->info('✅ Keine Zuweisungen vorhanden. Nichts zu tun.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('⚠️  ACHTUNG: Diese Aktion löscht ALLE Zuweisungen!');
        $this->line('   - Alle Einträge in career_level_user werden gelöscht');
        $this->line('   - Alle users.career_level_id werden auf NULL gesetzt');
        $this->line('   - Alle direkten Modul-Zuweisungen (module_assignments) werden gelöscht');
        $this->line('   - Danach wird Personio-Sync ausgeführt (nur Personio-basierte Zuweisungen werden wiederhergestellt)');
        $this->newLine();

        $this->info('Was bleibt erhalten:');
        $this->line('   ✓ Module, Beschreibungen, Namings');
        $this->line('   ✓ Karrierestufen- und Karrierepfad-Definitionen');
        $this->line('   ✓ Enrollments und Fortschritt');
        $this->line('   ✓ User-Accounts');
        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm('Möchtest du fortfahren?')) {
                $this->info('Abgebrochen.');
                return self::SUCCESS;
            }
        }

        $this->newLine();
        $this->info('Schritt 1/4: Lösche career_level_user Einträge...');
        $deletedPivot = DB::table('career_level_user')->delete();
        $this->line("   → {$deletedPivot} Einträge gelöscht");

        $this->info('Schritt 2/4: Setze users.career_level_id auf NULL...');
        $updatedUsers = User::whereNotNull('career_level_id')->update(['career_level_id' => null]);
        $this->line("   → {$updatedUsers} User aktualisiert");

        $this->info('Schritt 3/4: Lösche direkte Modul-Zuweisungen...');
        $deletedModules = DB::table('module_assignments')->delete();
        $this->line("   → {$deletedModules} Modul-Zuweisungen gelöscht");

        if ($this->option('skip-sync')) {
            $this->newLine();
            $this->warn('⚠️  Personio-Sync übersprungen (--skip-sync)');
            $this->line('   Führe manuell `php artisan academy:sync-personio` aus, um Zuweisungen wiederherzustellen.');
        } else {
            $this->newLine();
            $this->info('Schritt 4/4: Führe Personio-Sync aus...');

            if (! $personio->isConfigured()) {
                $this->error('Personio API ist nicht konfiguriert.');
                $this->line('Bitte PERSONIO_CLIENT_ID und PERSONIO_CLIENT_SECRET in der .env setzen.');
                $this->line('Reset wurde durchgeführt, aber Zuweisungen wurden nicht wiederhergestellt.');
                return self::FAILURE;
            }

            $log = $personio->syncEmployees();

            if ($log->isSuccess()) {
                $this->line('   → Sync erfolgreich');
            } else {
                $this->warn("   → Sync mit Status: {$log->status}");
            }

            $details = $log->details ?? [];
            $assigned = $details['users_career_assigned'] ?? 0;
            $this->line("   → {$assigned} User haben Karrierestufen erhalten");
        }

        $this->newLine();
        $this->info('📊 Ergebnis nach Reset:');

        $newPivotCount = DB::table('career_level_user')->count();
        $newUsersWithLevel = User::whereNotNull('career_level_id')->count();
        $newModuleAssignments = DB::table('module_assignments')->count();

        $this->table(
            ['Metrik', 'Vorher', 'Nachher'],
            [
                ['Einträge in career_level_user', $pivotCount, $newPivotCount],
                ['User mit career_level_id', $usersWithLevel, $newUsersWithLevel],
                ['Direkte Modul-Zuweisungen', $moduleAssignments, $newModuleAssignments],
            ]
        );

        $this->newLine();
        $this->info('✅ Reset abgeschlossen!');

        $unmapped = PersonioService::getUsersWithoutCareerPath();
        if ($unmapped > 0) {
            $this->newLine();
            $this->warn("⚠️  {$unmapped} Mitarbeiter haben keinen Karrierepfad (Overhead/Head-of oder fehlendes Mapping).");
        }

        return self::SUCCESS;
    }
}
