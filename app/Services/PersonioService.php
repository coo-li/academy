<?php

namespace App\Services;

use App\Models\CareerLevel;
use App\Models\CareerPath;
use App\Models\PersonioPositionMapping;
use App\Models\PersonioSyncLog;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
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

                    $employees[] = [
                        'personio_id' => (string) ($employee['attributes']['id']['value'] ?? null),
                        'first_name' => $this->extractAttribute($attrs, 'first_name'),
                        'last_name' => $this->extractAttribute($attrs, 'last_name'),
                        'email' => $this->extractAttribute($attrs, 'email'),
                        'position' => $this->extractAttribute($attrs, 'position'),
                        'department' => $this->extractDepartment($attrs),
                        'level_raw' => $this->extractCustomField($attrs, self::CUSTOM_FIELD_CAREER_LEVEL),
                        'path_raw' => $this->extractCustomField($attrs, self::CUSTOM_FIELD_CAREER_PATH),
                        'supervisor_personio_id' => $this->extractSupervisorId($attrs),
                    ];
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
                $this->applyCareerMapping($user);
                $this->tryAutoMatch($user);

            } catch (\Throwable $e) {
                $errors[] = "Fehler bei {$emp['email']}: {$e->getMessage()}";
                Log::error('Personio: Sync error for employee.', [
                    'email' => $emp['email'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->syncHeadOfRelationships($employees);

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
            ],
            'finished_at' => now(),
        ]);

        $this->ensurePositionMappingsExist($employees);

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
     * Find the mapping for a user based on position + level + path combination.
     */
    protected function findMappingForUser(User $user): ?PersonioPositionMapping
    {
        if (! $user->personio_position) {
            return null;
        }

        return PersonioPositionMapping::where('personio_position', $user->personio_position)
            ->where('personio_level_raw', $user->personio_level_raw)
            ->where('personio_path_raw', $user->personio_path_raw)
            ->first();
    }

    /**
     * Apply career path mapping based on position + level + path combination.
     */
    public function applyCareerMapping(User $user): bool
    {
        $mapping = $this->findMappingForUser($user);

        if (! $mapping || ! $mapping->isMapped()) {
            return false;
        }

        if ($user->career_level_id !== $mapping->career_level_id) {
            $user->update(['career_level_id' => $mapping->career_level_id]);
            return true;
        }

        return false;
    }

    /**
     * Attempt to auto-match a user to a CareerPath based on personio_path_raw.
     * Handles comma-separated path values (e.g. "Expert Path,Leadership Path")
     * by using the first matching CareerPath.
     */
    public function tryAutoMatch(User $user): bool
    {
        if ($user->career_level_id) {
            return false;
        }

        if (! $user->personio_path_raw) {
            return false;
        }

        $pathNames = array_map('trim', explode(',', $user->personio_path_raw));
        $careerPath = null;

        foreach ($pathNames as $pathName) {
            $careerPath = CareerPath::whereRaw('LOWER(name) = ?', [Str::lower($pathName)])->first();
            if ($careerPath) {
                break;
            }
        }

        if (! $careerPath) {
            return false;
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
            return false;
        }

        $user->update(['career_level_id' => $level->id]);

        $mapping = $this->findMappingForUser($user);
        if ($mapping && ! $mapping->isMapped()) {
            $mapping->update([
                'career_path_id' => $careerPath->id,
                'career_level_id' => $level->id,
                'is_auto_matched' => true,
            ]);
        }

        return true;
    }

    /**
     * Ensure all unique position+level+path combinations exist in the mapping table.
     */
    protected function ensurePositionMappingsExist(array $employees): void
    {
        $combos = collect($employees)
            ->filter(fn ($emp) => filled($emp['position']))
            ->map(fn ($emp) => [
                'position' => $emp['position'],
                'level' => $emp['level_raw'],
                'path' => $emp['path_raw'],
            ])
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

    public static function getLastSync(): ?PersonioSyncLog
    {
        return PersonioSyncLog::latest('started_at')->first();
    }

    public static function getUsersWithoutCareerPath(): int
    {
        return User::whereNotNull('personio_id')
            ->whereNull('career_level_id')
            ->count();
    }
}
