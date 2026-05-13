<?php

namespace App\Http\Controllers;

use App\Models\PersonioSyncLog;
use App\Models\Role;
use App\Models\User;
use App\Services\AsanaService;
use App\Services\GoogleCalendarService;
use App\Services\PersonioService;

class AdminSettingsController extends Controller
{
    public function index(
        PersonioService $personio,
        AsanaService $asana,
        GoogleCalendarService $calendar,
    ) {
        $integrations = [
            'personio' => [
                'name' => 'Personio',
                'description' => 'Mitarbeiter-Sync & Karrierepfad-Zuordnung',
                'configured' => $personio->isConfigured(),
                'env_keys' => ['PERSONIO_CLIENT_ID', 'PERSONIO_CLIENT_SECRET'],
                'last_sync' => PersonioService::getLastSync(),
                'stats' => [
                    'Personio-User' => User::whereNotNull('personio_id')->count(),
                    'Ohne Karrierepfad' => PersonioService::getUsersWithoutCareerPath(),
                ],
                'admin_url' => route('admin.matrix.index'),
                'admin_label' => 'Karriere-Matrix öffnen',
            ],
            'asana' => [
                'name' => 'Asana',
                'description' => 'Automatische Task-Erstellung bei Kursbuchungen',
                'configured' => $asana->isConfigured(),
                'env_keys' => ['ASANA_ACCESS_TOKEN', 'ASANA_PROJECT_ID'],
                'last_sync' => null,
                'stats' => [],
                'admin_url' => null,
                'admin_label' => null,
            ],
            'google' => [
                'name' => 'Google Calendar',
                'description' => 'Workshop-Termine mit Google Calendar synchronisieren',
                'configured' => $calendar->isConfigured(),
                'env_keys' => [
                    'GOOGLE_CALENDAR_ID',
                    ['label' => 'GCS Credentials', 'ok' => file_exists(storage_path('app/google-auth.json'))],
                ],
                'last_sync' => null,
                'stats' => [],
                'admin_url' => null,
                'admin_label' => null,
            ],
        ];

        $roleStats = Role::withCount('users')->get()->pluck('users_count', 'name');
        $userStats = [
            'total' => User::count(),
            'active' => User::active()->count(),
            'archived' => User::archived()->count(),
        ];
        foreach ($roleStats as $roleName => $count) {
            $userStats[$roleName] = $count;
        }

        return view('admin.settings.index', compact('integrations', 'userStats'));
    }
}
