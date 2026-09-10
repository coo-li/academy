<?php

namespace App\Services;

use App\Models\BudgetEntry;
use App\Models\BudgetSyncProject;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrafficDesignBudgetService
{
    protected string $budgetApiUrl;
    protected string $tmApiUrl;
    protected ?string $apiKey;
    protected int $timeout;

    protected const MONTH_MAP = [
        'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4,
        'may' => 5, 'jun' => 6, 'jul' => 7, 'aug' => 8,
        'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
    ];

    public function __construct()
    {
        $this->budgetApiUrl = config('services.trafficdesign.budget_api_url', 'https://budgets.trafficdesign.de/api');
        $this->tmApiUrl = config('services.trafficdesign.tm_api_url', 'https://tm-test.trafficdesign.de/api');
        $this->apiKey = config('services.trafficdesign.api_key');
        $this->timeout = config('services.trafficdesign.timeout', 120);
    }

    /**
     * Check if the service is properly configured.
     */
    public function isConfigured(): bool
    {
        return config('services.trafficdesign.enabled', false)
            && filled($this->apiKey)
            && $this->apiKey !== 'DEIN_API_KEY_HIER';
    }

    /**
     * Check if the service is enabled.
     */
    public function isEnabled(): bool
    {
        return config('services.trafficdesign.enabled', false);
    }

    /**
     * Main sync method - fetches budgets from API and stores them.
     * Only syncs projects that are configured in budget_sync_projects table.
     */
    public function syncBudgets(int $year, bool $dryRun = false): array
    {
        $results = [
            'year' => $year,
            'dry_run' => $dryRun,
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'filtered_out' => 0,
            'errors' => [],
            'user_mapping_errors' => [],
        ];

        if (!$this->isConfigured()) {
            $results['errors'][] = 'TrafficDesign Budget Service is not configured. Check TRAFFICDESIGN_* env vars.';
            return $results;
        }

        // Hole konfigurierte Projektnamen
        $allowedProjectNames = BudgetSyncProject::active()->pluck('project_name')->toArray();
        
        if (empty($allowedProjectNames)) {
            $results['errors'][] = 'Keine Projekte konfiguriert. Bitte Projekte in Admin > Budget-Sync Projekte hinzufügen.';
            return $results;
        }

        Log::info('TrafficDesign Budget Sync started', [
            'year' => $year, 
            'dry_run' => $dryRun,
            'allowed_projects' => count($allowedProjectNames),
        ]);

        // Fetch budgets for both types: used (Ist) and available (Soll)
        foreach (['budget_used', 'budget_available'] as $budgetType) {
            $apiResults = $this->syncBudgetType($year, $budgetType, $allowedProjectNames, $dryRun);
            
            $results['processed'] += $apiResults['processed'];
            $results['created'] += $apiResults['created'];
            $results['updated'] += $apiResults['updated'];
            $results['skipped'] += $apiResults['skipped'];
            $results['filtered_out'] += $apiResults['filtered_out'];
            $results['errors'] = array_merge($results['errors'], $apiResults['errors']);
            $results['user_mapping_errors'] = array_merge(
                $results['user_mapping_errors'],
                $apiResults['user_mapping_errors']
            );
        }

        Log::info('TrafficDesign Budget Sync completed', $results);

        return $results;
    }

    /**
     * Sync a specific budget type (used or available).
     */
    protected function syncBudgetType(int $year, string $budgetType, array $allowedProjectNames, bool $dryRun): array
    {
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'filtered_out' => 0,
            'errors' => [],
            'user_mapping_errors' => [],
        ];

        try {
            $allBudgets = $this->fetchBudgetsByProject($year, $budgetType);
        } catch (\Exception $e) {
            $results['errors'][] = "Failed to fetch {$budgetType}: " . $e->getMessage();
            return $results;
        }

        if (empty($allBudgets)) {
            Log::info("No budgets found for {$budgetType}", ['year' => $year]);
            return $results;
        }

        // API liefert numerische Arrays: [0]=Kunde, [1]=Projekt, [2-13]=Jan-Dez
        // Filter: nur konfigurierte Projektnamen
        $budgets = collect($allBudgets)->filter(function ($budget) use ($allowedProjectNames) {
            $projectName = $budget[1] ?? $budget['project_name'] ?? null;
            if (!$projectName) return false;
            
            foreach ($allowedProjectNames as $allowed) {
                if (stripos($projectName, $allowed) !== false || stripos($allowed, $projectName) !== false) {
                    return true;
                }
            }
            return false;
        })->values()->all();

        $results['filtered_out'] = count($allBudgets) - count($budgets);

        Log::info("Filtered budgets for {$budgetType}", [
            'total_from_api' => count($allBudgets),
            'after_filter' => count($budgets),
            'filtered_out' => $results['filtered_out'],
        ]);

        // Normalize budget_type to internal format
        $internalBudgetType = $budgetType === 'budget_used' 
            ? BudgetEntry::BUDGET_TYPE_USED 
            : BudgetEntry::BUDGET_TYPE_AVAILABLE;

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($budgets as $budget) {
                $entryResults = $this->processBudgetEntry($budget, $year, $internalBudgetType, $dryRun);
                
                $results['processed'] += $entryResults['processed'];
                $results['created'] += $entryResults['created'];
                $results['updated'] += $entryResults['updated'];
                $results['skipped'] += $entryResults['skipped'];
                
                if (!empty($entryResults['error'])) {
                    $results['errors'][] = $entryResults['error'];
                }
                if (!empty($entryResults['user_mapping_error'])) {
                    $results['user_mapping_errors'][] = $entryResults['user_mapping_error'];
                }
            }

            if (!$dryRun) {
                DB::commit();
            }
        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollBack();
            }
            $results['errors'][] = "Transaction failed for {$budgetType}: " . $e->getMessage();
            Log::error('TrafficDesign Budget Sync transaction failed', [
                'budget_type' => $budgetType,
                'error' => $e->getMessage(),
            ]);
        }

        return $results;
    }

    /**
     * Process a single budget entry from the API.
     * API format: [0]=customer_name, [1]=project_name, [2-13]=jan-dec values
     */
    protected function processBudgetEntry(array $budget, int $year, string $budgetType, bool $dryRun): array
    {
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'error' => null,
            'user_mapping_error' => null,
        ];

        // Handle both numeric array format and associative array format
        $customerName = $budget[0] ?? $budget['customer_name'] ?? 'Unknown Customer';
        $projectName = $budget[1] ?? $budget['project_name'] ?? 'Unknown Project';
        $projectId = $budget['project_id'] ?? null;
        $label = "{$customerName}: {$projectName}";

        // Try to find accountable user if available
        $user = null;
        if (!empty($budget['accountable_name'])) {
            $user = $this->findUser($budget['accountable_name']);
            if (!$user) {
                $results['user_mapping_error'] = "User not found: {$budget['accountable_name']} for project {$projectName}";
            }
        }

        // Process each month
        // API format: [2]=jan, [3]=feb, ..., [13]=dec
        foreach (self::MONTH_MAP as $monthKey => $monthNum) {
            // Support both numeric array format (index 2-13) and associative (jan-dec)
            $numericIndex = $monthNum + 1; // jan=2, feb=3, ..., dec=13
            $amount = $budget[$numericIndex] ?? $budget[$monthKey] ?? 0;
            
            // Skip zero amounts
            if (empty($amount) || $amount == 0) {
                continue;
            }

            $results['processed']++;

            $date = Carbon::create($year, $monthNum, 1);
            
            $entryData = [
                'user_id' => $user?->id,
                'project_id' => $projectId,
                'label' => $label,
                'category' => $customerName,
                'budget_name' => $projectName,
                'type' => BudgetEntry::TYPE_OTHER_INTERNAL,
                'budget_type' => $budgetType,
                'cost_type' => BudgetEntry::COST_TYPE_MONETARY,
                'amount' => (float) $amount,
                'date' => $date,
                'month' => $monthNum,
                'year' => $year,
                'is_deductible_from_allowance' => false,
            ];

            if ($dryRun) {
                $results['skipped']++;
                continue;
            }

            // Use updateOrCreate for deduplication
            $entry = BudgetEntry::updateOrCreate(
                [
                    'label' => $label,
                    'budget_type' => $budgetType,
                    'month' => $monthNum,
                    'year' => $year,
                    'project_id' => $projectId,
                ],
                $entryData
            );

            if ($entry->wasRecentlyCreated) {
                $results['created']++;
            } else {
                $results['updated']++;
            }
        }

        return $results;
    }

    /**
     * Fetch budgets by project from the Budget Tracker API.
     */
    public function fetchBudgetsByProject(int $year, string $budgetType = 'mixed'): array
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => false]) // API uses self-signed cert
            ->get("{$this->budgetApiUrl}/getBudgetsByProject", [
                'year' => $year,
                'budget_type' => $budgetType,
                'api_key' => $this->apiKey,
            ]);

        if (!$response->successful()) {
            Log::error('TrafficDesign Budget API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("Budget API request failed: HTTP {$response->status()}");
        }

        $data = $response->json();
        
        // API might return data directly or wrapped
        return is_array($data) ? $data : [];
    }

    /**
     * Fetch project budgets with accountable info from TM API.
     */
    public function fetchProjectBudgets(int $projectId, int $year, string $budgetType = 'mixed'): array
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => false])
            ->get("{$this->tmApiUrl}/getProjectBudgets", [
                'project_id' => $projectId,
                'year' => $year,
                'budget_type' => $budgetType,
                'level' => 'budget_level',
                'show_accountable' => 1,
                'api_key' => $this->apiKey,
            ]);

        if (!$response->successful()) {
            Log::error('TrafficDesign TM API request failed', [
                'endpoint' => 'getProjectBudgets',
                'project_id' => $projectId,
                'status' => $response->status(),
            ]);
            throw new \RuntimeException("TM API request failed: HTTP {$response->status()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Sync project budgets from TM API with monthly granularity.
     * This uses getProjectBudgets with show_accountable=1 to get per-person monthly data.
     */
    public function syncProjectBudgetsFromTM(int $year, bool $dryRun = false): array
    {
        $results = [
            'year' => $year,
            'dry_run' => $dryRun,
            'projects_processed' => 0,
            'entries_created' => 0,
            'entries_updated' => 0,
            'entries_skipped' => 0,
            'user_not_found' => 0,
            'errors' => [],
        ];

        if (!$this->isConfigured()) {
            $results['errors'][] = 'TrafficDesign Budget Service is not configured.';
            return $results;
        }

        // Hole alle konfigurierten Projekte mit ihren project_ids
        $configuredProjects = BudgetSyncProject::active()->get();
        
        if ($configuredProjects->isEmpty()) {
            $results['errors'][] = 'Keine Projekte konfiguriert.';
            return $results;
        }

        Log::info('TrafficDesign TM Project Budget Sync started', [
            'year' => $year,
            'projects' => $configuredProjects->count(),
            'dry_run' => $dryRun,
        ]);

        // Cache für User-Mapping (Name -> User)
        $userCache = User::whereNull('archived_at')
            ->get()
            ->keyBy(fn($u) => strtolower(trim($u->name)));

        foreach ($configuredProjects as $project) {
            $projectId = (int) $project->project_id;
            
            if ($projectId <= 0) {
                $results['errors'][] = "Ungültige Project-ID für: {$project->project_name}";
                continue;
            }

            try {
                // Hole IST-Werte (budget_used)
                $usedData = $this->fetchProjectBudgets($projectId, $year, 'budget_used');
                $this->processProjectBudgetEntries(
                    $usedData, 
                    $year, 
                    BudgetEntry::BUDGET_TYPE_USED, 
                    $project, 
                    $userCache, 
                    $results, 
                    $dryRun
                );

                // Hole PLAN-Werte (budget_available)
                $availableData = $this->fetchProjectBudgets($projectId, $year, 'budget_available');
                $this->processProjectBudgetEntries(
                    $availableData, 
                    $year, 
                    BudgetEntry::BUDGET_TYPE_AVAILABLE, 
                    $project, 
                    $userCache, 
                    $results, 
                    $dryRun
                );

                $results['projects_processed']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Fehler bei Projekt {$project->project_name}: " . $e->getMessage();
                Log::error('TrafficDesign TM sync error', [
                    'project' => $project->project_name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('TrafficDesign TM Project Budget Sync completed', $results);

        return $results;
    }

    /**
     * Process budget entries from TM API getProjectBudgets response.
     */
    protected function processProjectBudgetEntries(
        array $entries, 
        int $year, 
        string $budgetType, 
        BudgetSyncProject $project,
        $userCache,
        array &$results,
        bool $dryRun
    ): void {
        foreach ($entries as $entry) {
            $accountableName = $entry['accountableUser'] ?? null;
            $title = $entry['title'] ?? 'Unbekannt';
            $months = $entry['months'] ?? [];
            $taskId = $entry['taskId'] ?? null;

            if (empty($accountableName)) {
                continue;
            }

            // User finden anhand des Namens
            $user = $userCache[strtolower(trim($accountableName))] ?? null;
            
            if (!$user) {
                // Versuche Teilmatch (Vorname Nachname)
                $user = $this->findUserByNameFuzzy($accountableName, $userCache);
            }

            if (!$user) {
                $results['user_not_found']++;
                Log::debug('User not found for budget entry', [
                    'accountable_name' => $accountableName,
                    'title' => $title,
                ]);
                continue;
            }

            // Verarbeite jeden Monat (Index 0 = Januar, 11 = Dezember)
            foreach ($months as $monthIndex => $amount) {
                $monthNum = $monthIndex + 1; // 1-12
                
                if (empty($amount) || $amount == 0) {
                    continue;
                }

                $entryData = [
                    'user_id' => $user->id,
                    'project_id' => $project->project_id,
                    'label' => $title,
                    'category' => 'Teamziele',
                    'budget_name' => $project->project_name,
                    'type' => BudgetEntry::TYPE_TEAM_GOAL,
                    'budget_type' => $budgetType,
                    'cost_type' => BudgetEntry::COST_TYPE_MONETARY,
                    'amount' => (float) $amount,
                    'date' => Carbon::create($year, $monthNum, 1),
                    'month' => $monthNum,
                    'year' => $year,
                    'is_deductible_from_allowance' => false,
                ];

                if ($dryRun) {
                    $results['entries_skipped']++;
                    continue;
                }

                $dbEntry = BudgetEntry::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'label' => $title,
                        'budget_type' => $budgetType,
                        'month' => $monthNum,
                        'year' => $year,
                    ],
                    $entryData
                );

                if ($dbEntry->wasRecentlyCreated) {
                    $results['entries_created']++;
                } else {
                    $results['entries_updated']++;
                }
            }
        }
    }

    /**
     * Try to find user by fuzzy name match.
     */
    protected function findUserByNameFuzzy(string $name, $userCache): ?User
    {
        $nameLower = strtolower(trim($name));
        
        // Direkte Suche
        if (isset($userCache[$nameLower])) {
            return $userCache[$nameLower];
        }

        // Suche mit ähnlichen Namen
        foreach ($userCache as $key => $user) {
            // Prüfe ob einer im anderen enthalten ist
            if (str_contains($key, $nameLower) || str_contains($nameLower, $key)) {
                return $user;
            }
            
            // Prüfe Nachname
            $nameParts = explode(' ', $nameLower);
            $lastName = end($nameParts);
            if (strlen($lastName) > 3 && str_contains($key, $lastName)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Fetch all users from TM API for mapping.
     */
    public function fetchTmUsers(): array
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => false])
            ->get("{$this->tmApiUrl}/getUsers", [
                'api_key' => $this->apiKey,
            ]);

        if (!$response->successful()) {
            Log::error('TrafficDesign TM API request failed', [
                'endpoint' => 'getUsers',
                'status' => $response->status(),
            ]);
            throw new \RuntimeException("TM API getUsers failed: HTTP {$response->status()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch all projects from TM API.
     */
    public function fetchProjects(): array
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => false])
            ->get("{$this->tmApiUrl}/getProjects", [
                'api_key' => $this->apiKey,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("TM API getProjects failed: HTTP {$response->status()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch all customers from TM API.
     */
    public function fetchCustomers(): array
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => false])
            ->get("{$this->tmApiUrl}/getCustomers", [
                'api_key' => $this->apiKey,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("TM API getCustomers failed: HTTP {$response->status()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch capacity planning analysis for a specific user from TM API.
     * Returns planned and actual hours per project for a given time period.
     */
    public function fetchCapacityPlanningAnalysis(int $tmUserId, string $startDate, string $endDate): array
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => false])
            ->get("{$this->budgetApiUrl}/getCapacityPlanningAnalysis", [
                'user_id' => $tmUserId,
                'group_by' => 'project',
                'start' => $startDate,
                'end' => $endDate,
                'api_key' => $this->apiKey,
            ]);

        if (!$response->successful()) {
            Log::error('TrafficDesign API getCapacityPlanningAnalysis failed', [
                'tm_user_id' => $tmUserId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("API getCapacityPlanningAnalysis failed: HTTP {$response->status()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Fetch service areas from TM API.
     */
    public function fetchServiceAreas(): array
    {
        $response = Http::timeout($this->timeout)
            ->withOptions(['verify' => false])
            ->get("{$this->tmApiUrl}/getServiceAreas", [
                'api_key' => $this->apiKey,
            ]);

        if (!$response->successful()) {
            throw new \RuntimeException("TM API getServiceAreas failed: HTTP {$response->status()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Find a user by name or budget_tracker_id.
     */
    public function findUser(string $nameOrId): ?User
    {
        // First try by budget_tracker_id
        $user = User::where('budget_tracker_id', $nameOrId)->first();
        if ($user) {
            return $user;
        }

        // Then try by exact name match (case-insensitive)
        $normalized = $this->normalizeNameForMatching($nameOrId);
        return User::get()->first(function ($u) use ($normalized) {
            return $this->normalizeNameForMatching($u->name) === $normalized;
        });
    }

    /**
     * Normalize a name for matching (lowercase, trimmed, umlauts).
     */
    protected function normalizeNameForMatching(string $name): string
    {
        $name = mb_strtolower(trim($name));
        // Normalize umlauts both ways
        $name = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $name);
        return $name;
    }

    /**
     * Map TM API user to Academy user.
     */
    public function mapTmUserToAcademy(array $tmUser): ?User
    {
        // Try mapping by TM user ID stored in budget_tracker_id
        if (!empty($tmUser['id'])) {
            $user = User::where('budget_tracker_id', (string) $tmUser['id'])->first();
            if ($user) {
                return $user;
            }
        }

        // Try mapping by name
        if (!empty($tmUser['name'])) {
            return $this->findUser($tmUser['name']);
        }

        return null;
    }

    /**
     * Sync personal capacities (planned/actual hours) for all users with tm_user_id.
     * Creates budget entries with cost_type='time' for each user and project.
     */
    public function syncPersonCapacities(int $year, bool $dryRun = false): array
    {
        $results = [
            'year' => $year,
            'dry_run' => $dryRun,
            'users_processed' => 0,
            'entries_created' => 0,
            'entries_updated' => 0,
            'entries_skipped' => 0,
            'errors' => [],
        ];

        if (!$this->isConfigured()) {
            $results['errors'][] = 'TrafficDesign Budget Service is not configured.';
            return $results;
        }

        // Hole konfigurierte Projektnamen für Teamziele
        $allowedProjectNames = BudgetSyncProject::active()->pluck('project_name')->toArray();
        
        if (empty($allowedProjectNames)) {
            $results['errors'][] = 'Keine Projekte konfiguriert.';
            return $results;
        }

        Log::info('TrafficDesign Person Capacity Sync started', [
            'year' => $year,
            'dry_run' => $dryRun,
        ]);

        // Zeitraum für das Jahr
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        // Alle User mit TM User ID
        $users = User::whereNotNull('tm_user_id')->get();
        
        foreach ($users as $user) {
            try {
                $capacityData = $this->fetchCapacityPlanningAnalysis(
                    $user->tm_user_id,
                    $startDate,
                    $endDate
                );

                $results['users_processed']++;

                // Verarbeite die Projektzeilen
                $rows = $capacityData['rows'] ?? [];
                
                foreach ($rows as $row) {
                    $projectTitle = $row['project_title'] ?? '';
                    $projectId = $row['project_id'] ?? null;
                    
                    // Nur Teamziele-Projekte (oder konfigurierte Projekte)
                    $isRelevant = false;
                    foreach ($allowedProjectNames as $allowed) {
                        if (stripos($projectTitle, $allowed) !== false ||
                            stripos($projectTitle, 'Team-Ziele') !== false ||
                            stripos($projectTitle, 'Teamziele') !== false) {
                            $isRelevant = true;
                            break;
                        }
                    }
                    
                    if (!$isRelevant) {
                        continue;
                    }

                    $plannedHours = (float) ($row['planned_hours'] ?? 0);
                    $actualHours = (float) ($row['actual_hours'] ?? 0);

                    // Nur speichern wenn mindestens ein Wert > 0
                    if ($plannedHours == 0 && $actualHours == 0) {
                        continue;
                    }

                    // Ist-Stunden (actual)
                    if ($actualHours > 0) {
                        $entryData = [
                            'user_id' => $user->id,
                            'project_id' => $projectId,
                            'label' => $projectTitle,
                            'category' => 'Teamziele',
                            'budget_name' => $projectTitle,
                            'type' => BudgetEntry::TYPE_TEAM_GOAL,
                            'budget_type' => BudgetEntry::BUDGET_TYPE_USED,
                            'cost_type' => BudgetEntry::COST_TYPE_TIME,
                            'amount' => $actualHours,
                            'date' => Carbon::create($year, 1, 1),
                            'month' => null, // Jahressumme
                            'year' => $year,
                            'is_deductible_from_allowance' => false,
                        ];

                        if (!$dryRun) {
                            $entry = BudgetEntry::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'project_id' => $projectId,
                                    'budget_type' => BudgetEntry::BUDGET_TYPE_USED,
                                    'cost_type' => BudgetEntry::COST_TYPE_TIME,
                                    'year' => $year,
                                ],
                                $entryData
                            );

                            if ($entry->wasRecentlyCreated) {
                                $results['entries_created']++;
                            } else {
                                $results['entries_updated']++;
                            }
                        } else {
                            $results['entries_skipped']++;
                        }
                    }

                    // Soll-Stunden (planned)
                    if ($plannedHours > 0) {
                        $entryData = [
                            'user_id' => $user->id,
                            'project_id' => $projectId,
                            'label' => $projectTitle,
                            'category' => 'Teamziele',
                            'budget_name' => $projectTitle,
                            'type' => BudgetEntry::TYPE_TEAM_GOAL,
                            'budget_type' => BudgetEntry::BUDGET_TYPE_AVAILABLE,
                            'cost_type' => BudgetEntry::COST_TYPE_TIME,
                            'amount' => $plannedHours,
                            'date' => Carbon::create($year, 1, 1),
                            'month' => null, // Jahressumme
                            'year' => $year,
                            'is_deductible_from_allowance' => false,
                        ];

                        if (!$dryRun) {
                            $entry = BudgetEntry::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'project_id' => $projectId,
                                    'budget_type' => BudgetEntry::BUDGET_TYPE_AVAILABLE,
                                    'cost_type' => BudgetEntry::COST_TYPE_TIME,
                                    'year' => $year,
                                ],
                                $entryData
                            );

                            if ($entry->wasRecentlyCreated) {
                                $results['entries_created']++;
                            } else {
                                $results['entries_updated']++;
                            }
                        } else {
                            $results['entries_skipped']++;
                        }
                    }
                }
            } catch (\Exception $e) {
                $results['errors'][] = "User {$user->name} (TM ID {$user->tm_user_id}): " . $e->getMessage();
                Log::error('TrafficDesign Person Capacity Sync failed for user', [
                    'user_id' => $user->id,
                    'tm_user_id' => $user->tm_user_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('TrafficDesign Person Capacity Sync completed', $results);

        return $results;
    }

    /**
     * Test API connectivity.
     */
    public function testConnection(): array
    {
        $results = [
            'budget_api' => ['success' => false, 'message' => ''],
            'tm_api' => ['success' => false, 'message' => ''],
            'configured_projects' => BudgetSyncProject::active()->count(),
        ];

        // Test Budget Tracker API
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->get("{$this->budgetApiUrl}/getBudgetsByProject", [
                    'year' => date('Y'),
                    'budget_type' => 'budget_available',
                    'api_key' => $this->apiKey,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $count = is_array($data) ? count($data) : 0;
                $results['budget_api'] = [
                    'success' => true,
                    'message' => "Connected. Found {$count} budget entries.",
                ];
            } else {
                $results['budget_api']['message'] = "HTTP {$response->status()}: {$response->body()}";
            }
        } catch (\Exception $e) {
            $results['budget_api']['message'] = $e->getMessage();
        }

        // Test TM API
        try {
            $response = Http::timeout(10)
                ->withOptions(['verify' => false])
                ->get("{$this->tmApiUrl}/getServiceAreas", [
                    'api_key' => $this->apiKey,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $count = is_array($data) ? count($data) : 0;
                $results['tm_api'] = [
                    'success' => true,
                    'message' => "Connected. Found {$count} service areas.",
                ];
            } else {
                $results['tm_api']['message'] = "HTTP {$response->status()}: {$response->body()}";
            }
        } catch (\Exception $e) {
            $results['tm_api']['message'] = $e->getMessage();
        }

        return $results;
    }
}
