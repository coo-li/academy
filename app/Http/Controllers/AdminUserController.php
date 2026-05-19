<?php

namespace App\Http\Controllers;

use App\Models\CareerPath;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use App\Notifications\UserInvitation;
use App\Services\PersonioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'active');
        $search = $request->get('search');
        $roleFilter = $request->get('role');

        $query = User::with(['careerLevel.careerPath', 'roles', 'team', 'managedTeams']);

        if ($tab === 'archived') {
            $query->archived();
        } else {
            $query->active();
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($roleFilter) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $roleFilter));
        }

        $users = $query->orderBy('name')->paginate(25)->withQueryString();
        $careerPaths = CareerPath::with('levels')->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();

        $activeCount = User::active()->count();
        $archivedCount = User::archived()->count();
        $lastSync = PersonioService::getLastSync();
        $personioConfigured = app(PersonioService::class)->isConfigured();

        return view('admin.users.index', [
            'users' => $users,
            'careerPaths' => $careerPaths,
            'roles' => $roles,
            'teams' => $teams,
            'tab' => $tab,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'activeCount' => $activeCount,
            'archivedCount' => $archivedCount,
            'lastSync' => $lastSync,
            'personioConfigured' => $personioConfigured,
        ]);
    }

    public function updateRoles(Request $request, User $user)
    {
        $request->validate([
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $user->roles()->sync($request->roles);

        return back()->with('success', "Rollen für {$user->name} aktualisiert.");
    }

    public function updateManagedTeams(Request $request, User $user)
    {
        $request->validate([
            'managed_teams' => ['nullable', 'array'],
            'managed_teams.*' => ['exists:teams,id'],
        ]);

        $user->managedTeams()->sync($request->managed_teams ?? []);

        return back()->with('success', "Betreute Teams für {$user->name} aktualisiert.");
    }

    public function sendInvitation(User $user)
    {
        $token = Password::createToken($user);

        $user->notify(new UserInvitation($token));

        $user->update(['invited_at' => now()]);

        return back()->with('success', "Einladung an {$user->name} ({$user->email}) versendet.");
    }

    public function resetPassword(User $user)
    {
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', "Passwort-Reset-Link an {$user->name} ({$user->email}) gesendet.");
        }

        return back()->with('error', 'Passwort-Reset konnte nicht gesendet werden.');
    }

    public function archive(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Du kannst dich nicht selbst archivieren.');
        }

        $user->update(['archived_at' => now()]);

        DB::table('sessions')->where('user_id', $user->id)->delete();

        return back()->with('success', "{$user->name} wurde archiviert und hat keinen Zugang mehr.");
    }

    public function restore(User $user)
    {
        $user->update(['archived_at' => null]);

        return back()->with('success', "{$user->name} wurde wiederhergestellt.");
    }

    public function syncPersonio(PersonioService $personio)
    {
        if (! $personio->isConfigured()) {
            return back()->with('error', 'Personio API ist nicht konfiguriert. Bitte Client ID und Secret in .env eintragen.');
        }

        $log = $personio->syncEmployees();

        if ($log->isSuccess()) {
            $details = $log->details ?? [];
            $archivedCount = $details['users_archived'] ?? 0;
            $reactivatedCount = $details['users_reactivated'] ?? 0;
            $mappingsCleanedCount = $details['mappings_cleaned'] ?? 0;

            $message = "{$log->employees_fetched} Mitarbeiter abgerufen, {$log->users_created} neu angelegt, {$log->users_updated} aktualisiert.";

            if ($archivedCount > 0) {
                $message .= " {$archivedCount} archiviert.";
            }
            if ($reactivatedCount > 0) {
                $message .= " {$reactivatedCount} reaktiviert.";
            }
            if ($mappingsCleanedCount > 0) {
                $message .= " {$mappingsCleanedCount} verwaiste Positions-Mappings entfernt.";
            }

            return back()->with('success', "Personio-Sync erfolgreich: {$message}");
        }

        return back()->with('error', 'Personio-Sync fehlgeschlagen: ' . ($log->error_message ?? 'Unbekannter Fehler'));
    }

    public function assignCareerPath(Request $request, User $user)
    {
        $request->validate([
            'career_level_id' => ['nullable', 'exists:career_levels,id'],
        ]);

        if ($request->career_level_id) {
            $level = \App\Models\CareerLevel::find($request->career_level_id);
            $user->addCareerLevel($level);
            $label = $level->careerPath->name . ' – ' . $level->title;

            return back()->with('success', "Karrierepfad für {$user->name} hinzugefügt: {$label}");
        }

        $user->careerLevels()->detach();
        $user->syncPrimaryCareerLevel();

        return back()->with('success', "Alle Karrierepfade für {$user->name} entfernt.");
    }
}
