<?php

namespace App\Services;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\PersonioPositionMapping;
use App\Models\PersonioSyncLog;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PersonioService
{
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;

    const CUSTOM_FIELD_CAREER_LEVEL = 'dynamic_8199339';
    const CUSTOM_FIELD_CAREER_PATH = 'dynamic_12804009';

    public function __construct()
    {
        $this->baseUrl = config('services.personio.base_url', 'https://api.personio.de/v1');
        $this->clientId = config('services.personio.client_id');
        $this->clientSecret = config('services.personio.client_secret');
    }

    public function isConfigured(): bool
    {
        return filled($this->clientId)
            && filled($this->clientSecret)
            && $this->clientId !== 'DEIN_CLIENT_ID_HIER';
    }

    /**
     * Authenticate with Personio and cache the token for its validity period.
     */
    protected function getAccessToken(): ?string
    {
        return Cache::remember('personio_access_token', 300, function () {
            try {
                $response = Http::post("{$this->baseUrl}/auth", [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

                if ($response->successful()) {
                    return $response->json('data.token');
                }

                Log::error('Personio: Authentication failed.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } catch (\Throwable $e) {
                Log::error('Personio: Authentication exception.', [
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }

    /**
     * Fetch all active employees from Personio.
     *
     * @return array<int, array>|null
     */
    public function getActiveEmployees(): ?array
    {
        $token = $this->getAccessToken();

        if (! $token) {
            return null;
        }

        try {
            $employees = [];
            $offset = 0;
            $limit = 200;

            do {
                $response = Http::withToken($token)
                    ->get("{$this->baseUrl}/company/employees", [
                        'limit' => $limit,
                        'offset' => $offset,
                    ]);

                if (! $response->successful()) {
                    Log::error('Personio: Failed to fetch employees.', [
                        'status' => $response->status(),
                        'offset' => $offset,
                    ]);
                    break;
                }

                $data = $response->json('data', []);

                if (empty($data)) {
                    break;
                }

                foreach ($data as $employee) {
                    $attrs = $employee['attributes'] ?? [];
                    $status = $this->extractAttribute($attrs, 'status');

                    if ($status !== 'active') {
                        continue;
                    }

                    $pathRaw = $this->extractCustomField($attrs, self::CUSTOM_FIELD_CAREER_PATH);
                    $empData = [
                        'personio_id' => (string) ($employee['attributes']['id']['value'] ?? null),
                        'first_name' => $this->extractAttribute($attrs, 'first_name'),
                        'last_name' => $this->extractAttribute($attrs, 'last_name'),
                        'email' => $this->extractAttribute($attrs, 'email'),
                        'position' => $this->extractAttribute($attrs, 'position'),
                        'department' => $this->extractDepartment($attrs),
                        'level_raw' => $this->extractCustomField($attrs, self::CUSTOM_FIELD_CAREER_LEVEL),
                        'path_raw' => $pathRaw,
                        'supervisor_personio_id' => $this->extractSupervisorId($attrs),
                    ];

                    $employees[] = $empData;
                }

                $offset += $limit;
            } while (count($data) === $limit);

            return $employees;
        } catch (\Throwable $e) {
            Log::error('Personio: Exception fetching employees.', [
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    protected function extractAttribute(array $attrs, string $key): ?string
    {
        $value = $attrs[$key]['value'] ?? null;

        return is_string($value) ? $value : null;
    }

    protected function extractSupervisorId(array $attrs): ?string
    {
        $supervisor = $attrs['supervisor']['value'] ?? null;

        if (is_array($supervisor)) {
            $id = $supervisor['attributes']['id']['value']
                ?? $supervisor['id']['value']
                ?? $supervisor['id']
                ?? null;

            return $id !== null ? (string) $id : null;
        }

        return null;
    }

    protected function extractDepartment(array $attrs): ?string
    {
        $dept = $attrs['department']['value'] ?? null;

        if (is_array($dept)) {
            return $dept['attributes']['name'] ?? null;
        }

        return is_string($dept) ? $dept : null;
    }

    /**
     * Extract a Personio dynamic/custom field value. These can be stored as
     * simple scalars or as nested objects with a label.
     */
    protected function extractCustomField(array $attrs, string $fieldId): ?string
    {
        $field = $attrs[$fieldId] ?? null;

        if ($field === null) {
            return null;
        }

        $value = $field['value'] ?? null;

        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return $value['attributes']['name']
                ?? $value['label']
                ?? $value['name']
                ?? json_encode($value);
        }

        return null;
    }

    /**
     * Run the full sync process: fetch employees, create/update users, apply career mappings.
     */
    public function syncEmployees(): PersonioSyncLog
    {
        $log = PersonioSyncLog::create([
            'status' => 'error',
            'started_at' => now(),
        ]);

        if (! $this->isConfigured()) {
            $log->update([
                'error_message' => 'Personio API nicht konfiguriert. Bitte Client ID und Secret in .env eintragen.',
                'finished_at' => now(),
            ]);

            return $log;
        }

        $employees = $this->getActiveEmployees();

        if ($employees === null) {
            $log->update([
                'error_message' => 'Fehler beim Abrufen der Mitarbeiter von Personio.',
                'finished_at' => now(),
            ]);

            return $log;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($employees as $emp) {
            try {
                if (empty($emp['email'])) {
                    $skipped++;
                    continue;
                }

                $user = User::where('personio_id', $emp['personio_id'])
                    ->orWhere('email', $emp['email'])
                    ->first();

                $isNew = ! $user;

                if ($isNew) {
                    $user = User::create([
                        'name' => trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')),
                        'email' => $emp['email'],
                        'password' => bcrypt(Str::random(32)),
                        'role' => 'student',
                        'personio_id' => $emp['personio_id'],
                        'personio_position' => $emp['position'],
                        'personio_department' => $emp['department'],
                        'personio_level_raw' => $emp['level_raw'],
                        'personio_path_raw' => $emp['path_raw'],
                        'personio_synced_at' => now(),
                    ]);

                    $mitarbeitenderRole = Role::where('slug', 'mitarbeitender')->first();
                    if ($mitarbeitenderRole) {
                        $user->roles()->syncWithoutDetaching([$mitarbeitenderRole->id]);
                    }

                    $created++;
                } else {
                    $user->update([
                        'name' => trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? '')),
                        'email' => $emp['email'],
                        'personio_id' => $emp['personio_id'],
                        'personio_position' => $emp['position'],
                        'personio_department' => $emp['department'],
                        'personio_level_raw' => $emp['level_raw'],
                        'personio_path_raw' => $emp['path_raw'],
                        'personio_synced_at' => now(),
                    ]);
                    $updated++;
                }

                $this->syncTeamForUser($user, $emp['department']);

            } catch (\Throwable $e) {
                $errors[] = "Fehler bei {$emp['email']}: {$e->getMessage()}";
                Log::error('Personio: Sync error for employee.', [
                    'email' => $emp['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $activePersonioIds = collect($employees)->pluck('personio_id')->filter()->toArray();

        $archived = 0;
        $reactivated = 0;
        $archivedNames = [];

        $usersToArchive = User::whereNotNull('personio_id')
            ->whereNotIn('personio_id', $activePersonioIds)
            ->whereNull('archived_at')
            ->get();

        foreach ($usersToArchive as $userToArchive) {
            $userToArchive->update(['archived_at' => now()]);
            DB::table('sessions')->where('user_id', $userToArchive->id)->delete();
            $archivedNames[] = $userToArchive->name;
            $archived++;
        }

        $usersToReactivate = User::whereNotNull('personio_id')
            ->whereIn('personio_id', $activePersonioIds)
            ->whereNotNull('archived_at')
            ->get();

        foreach ($usersToReactivate as $userToReactivate) {
            $userToReactivate->update(['archived_at' => null]);
            $reactivated++;
        }

        $this->syncHeadOfRelationships($employees);
        $this->ensurePositionMappingsExist($employees);
        $mappingsCleaned = $this->cleanupOrphanedMappings();
        $autoMapped = $this->autoMapUnmappedPositions();

        $usersAssigned = 0;
        $usersWithPersonio = User::whereNotNull('personio_id')
            ->whereNull('archived_at')
            ->with('careerLevels')
            ->get();
        foreach ($usersWithPersonio as $user) {
            if ($this->applyCareerMapping($user)) {
                $usersAssigned++;
            }
            $this->tryAutoMatch($user);
        }

        $status = empty($errors) ? 'success' : (($created + $updated > 0) ? 'partial' : 'error');

        $log->update([
            'status' => $status,
            'employees_fetched' => count($employees),
            'users_created' => $created,
            'users_updated' => $updated,
            'users_skipped' => $skipped,
            'error_message' => ! empty($errors) ? implode("\n", $errors) : null,
            'details' => [
                'errors' => $errors,
                'positions_found' => collect($employees)->pluck('position')->filter()->unique()->values()->all(),
                'mappings_auto_matched' => $autoMapped,
                'mappings_cleaned' => $mappingsCleaned,
                'users_career_assigned' => $usersAssigned,
                'users_archived' => $archived,
                'users_reactivated' => $reactivated,
                'archived_names' => $archivedNames,
            ],
            'finished_at' => now(),
        ]);

        return $log;
    }

    /**
     * Find or create a team for the given department name and assign the user.
     */
    protected function syncTeamForUser(User $user, ?string $department): void
    {
        if (! filled($department)) {
            return;
        }

        $team = Team::firstOrCreate(
            ['personio_department' => $department],
            ['name' => $department]
        );

        if ($user->team_id !== $team->id) {
            $user->update(['team_id' => $team->id]);
        }
    }

    /**
     * Resolve supervisor_personio_id to head_of_user_id for all synced users.
     * Runs after the main sync loop so all users exist in the DB.
     */
    protected function syncHeadOfRelationships(array $employees): void
    {
        $supervisorMap = collect($employees)
            ->filter(fn ($emp) => filled($emp['supervisor_personio_id']))
            ->pluck('supervisor_personio_id', 'personio_id');

        if ($supervisorMap->isEmpty()) {
            return;
        }

        $personioIdToUserId = User::whereNotNull('personio_id')
            ->pluck('id', 'personio_id');

        foreach ($supervisorMap as $empPersonioId => $supervisorPersonioId) {
            $userId = $personioIdToUserId[$empPersonioId] ?? null;
            $headOfUserId = $personioIdToUserId[$supervisorPersonioId] ?? null;

            if ($userId && $headOfUserId) {
                User::where('id', $userId)
                    ->where(function ($q) use ($headOfUserId) {
                        $q->whereNull('head_of_user_id')
                          ->orWhere('head_of_user_id', '!=', $headOfUserId);
                    })
                    ->update(['head_of_user_id' => $headOfUserId]);
            }
        }
    }

    /**
     * Find all mappings for a user, splitting comma-separated paths into
     * individual lookups. Returns one mapping per individual path.
     */
    protected function findMappingsForUser(User $user): Collection
    {
        if (! $user->personio_position) {
            return collect();
        }

        $paths = $user->personio_path_raw
            ? array_map('trim', explode(',', $user->personio_path_raw))
            : [null];

        return collect($paths)->map(fn (?string $path) =>
            PersonioPositionMapping::where('personio_position', $user->personio_position)
                ->where('personio_level_raw', $user->personio_level_raw)
                ->where('personio_path_raw', $path)
                ->first()
        )->filter()->values();
    }

    /**
     * Apply all Karrierepfad mappings to user via pivot table.
     * Only adds NEW levels -- never removes or overwrites existing ones.
     */
    public function applyCareerMapping(User $user): bool
    {
        if (in_array(Str::lower(trim($user->personio_level_raw ?? '')), ['overhead', 'head of'])) {
            return false;
        }

        $mappings = $this->findMappingsForUser($user);
        $applied = false;

        foreach ($mappings as $mapping) {
            if (! $mapping->isMapped()) {
                continue;
            }

            if ($user->careerLevels->contains('id', $mapping->career_level_id)) {
                continue;
            }

            $user->addCareerLevel($mapping->careerLevel);
            $applied = true;
        }

        return $applied;
    }

    /**
     * Fallback auto-match: for each individual path in personio_path_raw,
     * find a matching CareerPath/Level and add to user's pivot.
     * Only adds paths the user doesn't already have.
     */
    public function tryAutoMatch(User $user): bool
    {
        if (! $user->personio_path_raw) {
            return false;
        }

        if (in_array(Str::lower(trim($user->personio_level_raw ?? '')), ['overhead', 'head of'])) {
            return false;
        }

        $pathNames = array_map('trim', explode(',', $user->personio_path_raw));
        $existingPathIds = $user->careerLevels->pluck('career_path_id')->toArray();
        $matched = false;

        foreach ($pathNames as $pathName) {
            $careerPath = $this->findCareerPathByPersonioName($pathName);

            if (! $careerPath || in_array($careerPath->id, $existingPathIds)) {
                continue;
            }

            $level = null;

            if ($user->personio_level_raw) {
                $level = $careerPath->levels()
                    ->whereRaw('LOWER(title) = ?', [Str::lower($user->personio_level_raw)])
                    ->first();
            }

            if (! $level) {
                $level = $careerPath->levels()->orderBy('level_number')->first();
            }

            if (! $level) {
                continue;
            }

            $user->addCareerLevel($level);
            $existingPathIds[] = $careerPath->id;
            $matched = true;

            $mapping = PersonioPositionMapping::where('personio_position', $user->personio_position)
                ->where('personio_level_raw', $user->personio_level_raw)
                ->where('personio_path_raw', $pathName)
                ->first();

            if ($mapping && ! $mapping->isMapped()) {
                $mapping->update([
                    'career_path_id' => $careerPath->id,
                    'career_level_id' => $level->id,
                    'is_auto_matched' => true,
                ]);
            }
        }

        return $matched;
    }

    /**
     * Ensure all unique position+level+path combinations exist in the mapping table.
     * Splits comma-separated paths into individual rows.
     */
    protected function ensurePositionMappingsExist(array $employees): void
    {
        $combos = collect($employees)
            ->filter(fn ($emp) => filled($emp['position']))
            ->flatMap(function ($emp) {
                $paths = filled($emp['path_raw'])
                    ? array_map('trim', explode(',', $emp['path_raw']))
                    : [null];

                return collect($paths)->map(fn (?string $path) => [
                    'position' => $emp['position'],
                    'level' => $emp['level_raw'],
                    'path' => $path,
                ]);
            })
            ->unique(fn ($c) => $c['position'] . '|' . $c['level'] . '|' . $c['path']);

        foreach ($combos as $combo) {
            PersonioPositionMapping::firstOrCreate(
                [
                    'personio_position' => $combo['position'],
                    'personio_level_raw' => $combo['level'],
                    'personio_path_raw' => $combo['path'],
                ],
                ['career_path_id' => null, 'career_level_id' => null]
            );
        }
    }

    /**
     * Auto-map all unmapped PersonioPositionMapping rows by matching
     * personio_path_raw to CareerPath.name and personio_level_raw to CareerLevel.title.
     * Skips "Overhead" and "Head of". Splits any remaining comma-separated rows first.
     * Returns the number of newly mapped rows.
     */
    public function autoMapUnmappedPositions(): int
    {
        $this->splitCommaSeparatedMappings();

        $unmapped = PersonioPositionMapping::whereNull('career_path_id')
            ->whereNull('career_level_id')
            ->whereNotNull('personio_path_raw')
            ->get();

        $count = 0;

        foreach ($unmapped as $mapping) {
            if (in_array(Str::lower(trim($mapping->personio_level_raw ?? '')), ['overhead', 'head of'])) {
                continue;
            }

            $careerPath = $this->findCareerPathByPersonioName($mapping->personio_path_raw);

            if (! $careerPath) {
                continue;
            }

            $level = null;

            if ($mapping->personio_level_raw) {
                $level = $careerPath->levels()
                    ->whereRaw('LOWER(title) = ?', [Str::lower($mapping->personio_level_raw)])
                    ->first();
            }

            if (! $level) {
                $level = $careerPath->levels()->orderBy('level_number')->first();
            }

            if (! $level) {
                continue;
            }

            $mapping->update([
                'career_path_id' => $careerPath->id,
                'career_level_id' => $level->id,
                'is_auto_matched' => true,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Split any remaining comma-separated personio_path_raw values into
     * individual mapping rows. Idempotent -- safe to call repeatedly.
     */
    protected function splitCommaSeparatedMappings(): void
    {
        $commaRows = PersonioPositionMapping::where('personio_path_raw', 'LIKE', '%,%')->get();

        foreach ($commaRows as $mapping) {
            $paths = array_map('trim', explode(',', $mapping->personio_path_raw));

            foreach ($paths as $path) {
                PersonioPositionMapping::firstOrCreate(
                    [
                        'personio_position' => $mapping->personio_position,
                        'personio_level_raw' => $mapping->personio_level_raw,
                        'personio_path_raw' => $path,
                    ],
                    ['career_path_id' => null, 'career_level_id' => null]
                );
            }

            $mapping->delete();
        }
    }

    /**
     * Remove PersonioPositionMapping entries that are no longer used by any active user.
     * Returns the number of deleted mappings.
     */
    protected function cleanupOrphanedMappings(): int
    {
        $activeUsers = User::active()
            ->whereNotNull('personio_position')
            ->get(['personio_position', 'personio_level_raw', 'personio_path_raw']);

        $activeComboKeys = $activeUsers->flatMap(function ($user) {
            $paths = filled($user->personio_path_raw)
                ? array_map('trim', explode(',', $user->personio_path_raw))
                : [null];

            return collect($paths)->map(fn ($path) =>
                $user->personio_position . '|' . $user->personio_level_raw . '|' . $path
            );
        })->unique()->toArray();

        return PersonioPositionMapping::all()
            ->filter(function ($mapping) use ($activeComboKeys) {
                $key = $mapping->personio_position . '|' .
                       $mapping->personio_level_raw . '|' .
                       $mapping->personio_path_raw;

                return ! in_array($key, $activeComboKeys);
            })
            ->each(fn ($mapping) => $mapping->delete())
            ->count();
    }

    /**
     * Find a CareerPath by a Personio path name using fuzzy matching.
     * Handles naming differences like "Expert Path" → "Expert (Digitalstrategie)",
     * "Accountmanagement Path" → "Account Management", "Leadership Path" → "Leadership".
     */
    protected function findCareerPathByPersonioName(string $personioPath): ?CareerPath
    {
        $lower = Str::lower(trim($personioPath));

        $exact = CareerPath::whereRaw('LOWER(name) = ?', [$lower])->first();
        if ($exact) {
            return $exact;
        }

        $normalized = preg_replace('/\s*path$/i', '', $lower);
        $normalized = preg_replace('/\s+/', '', $normalized);

        $allPaths = CareerPath::all();

        foreach ($allPaths as $path) {
            $dbNormalized = preg_replace('/\s+/', '', Str::lower($path->name));

            if ($dbNormalized === $normalized) {
                return $path;
            }

            if (Str::startsWith($dbNormalized, $normalized)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Run auto-mapping and then apply mappings to all users without full career coverage.
     * Returns [mappings_matched, users_assigned].
     */
    public function runAutoMapAndAssign(): array
    {
        $mapped = $this->autoMapUnmappedPositions();

        $assigned = 0;
        $users = User::whereNotNull('personio_id')->with('careerLevels')->get();

        foreach ($users as $user) {
            if ($this->applyCareerMapping($user)) {
                $assigned++;
            }
            $this->tryAutoMatch($user);
        }

        return ['mappings_matched' => $mapped, 'users_assigned' => $assigned];
    }

    public static function getLastSync(): ?PersonioSyncLog
    {
        return PersonioSyncLog::latest('started_at')->first();
    }

    public static function getUsersWithoutCareerPath(): int
    {
        return User::whereNotNull('personio_id')
            ->whereNull('career_level_id')
            ->whereNotIn('personio_level_raw', ['Overhead', 'Head of'])
            ->count();
    }
}
