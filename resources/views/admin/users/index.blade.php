<x-app-layout>
    @section('page-title', 'Nutzerverwaltung')

    <div class="space-y-6" x-data="{ tab: '{{ $tab }}' }">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Nutzerverwaltung</h1>
                <p class="text-surface-500 mt-1">{{ $activeCount }} aktive Nutzer, {{ $archivedCount }} archiviert</p>
            </div>
            @if($personioConfigured)
                <div class="flex items-center gap-3">
                    @if($lastSync)
                        <div class="flex items-center gap-1.5 text-xs text-surface-400">
                            @if($lastSync->isSuccess())
                                <span class="w-2 h-2 rounded-full bg-ui-success"></span>
                            @else
                                <span class="w-2 h-2 rounded-full bg-ui-error"></span>
                            @endif
                            Letzter Sync: {{ $lastSync->started_at->diffForHumans() }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('admin.users.syncPersonio') }}">
                        @csrf
                        <button type="submit" class="btn-primary btn-sm"
                                onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-sm\'></span> Sync läuft...'; this.form.submit();">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Personio Sync
                        </button>
                    </form>
                </div>
            @endif
        </div>

        {{-- Tabs --}}
        <div class="flex gap-1 border-b border-surface-200">
            <a href="{{ route('admin.users.index', ['tab' => 'active']) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'active' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-surface-500 hover:text-brand-dark hover:border-surface-300' }}">
                Aktive Nutzer
                <span class="ml-1.5 badge-neutral text-xs">{{ $activeCount }}</span>
            </a>
            <a href="{{ route('admin.users.index', ['tab' => 'archived']) }}"
               class="px-4 py-2.5 text-sm font-medium border-b-2 transition-colors {{ $tab === 'archived' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-surface-500 hover:text-brand-dark hover:border-surface-300' }}">
                Archiviert
                <span class="ml-1.5 badge-neutral text-xs">{{ $archivedCount }}</span>
            </a>
        </div>

        {{-- Search & Filter --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-1 gap-3">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Name oder E-Mail suchen..."
                           class="input-field w-full">
                </div>
                <div>
                    <select name="role" class="select-field" onchange="this.form.submit()">
                        <option value="">Alle Rollen</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->slug }}" {{ $roleFilter === $role->slug ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-secondary btn-sm">Suchen</button>
                @if($search || $roleFilter)
                    <a href="{{ route('admin.users.index', ['tab' => $tab]) }}" class="btn-ghost btn-sm">Zurücksetzen</a>
                @endif
            </form>
        </div>

        {{-- User Table --}}
        <div class="card-tool">
            <div class="card-tool-body !p-0">
                <div class="overflow-x-auto">
                    <table class="table-tool">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>E-Mail</th>
                                <th>Rollen</th>
                                <th>Personio</th>
                                <th>Status</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr x-data="{ showRoles: false, showTeams: false, showActions: false }">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="avatar-sm">
                                            <span>{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                        </div>
                                        <div>
                                            <span class="font-medium text-brand-dark">{{ $user->name }}</span>
                                            @if($user->personio_department)
                                                <span class="text-xs text-surface-400 block">{{ $user->personio_department }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm text-surface-500">{{ $user->email }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-1">
                                        @forelse($user->roles as $role)
                                            @php
                                                $badgeClass = match($role->slug) {
                                                    'admin' => 'badge-error',
                                                    'people_manager', 'head_of' => 'badge-warning',
                                                    'schulungsmanager' => 'badge-success',
                                                    'trainer' => 'badge-info',
                                                    default => 'badge-neutral',
                                                };
                                            @endphp
                                            <span class="{{ $badgeClass }} text-xs">{{ $role->name }}</span>
                                        @empty
                                            <span class="text-xs text-surface-400 italic">Keine Rolle</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @if($user->personio_id)
                                        <span class="badge-primary text-xs">Personio</span>
                                    @else
                                        <span class="text-xs text-surface-400">--</span>
                                    @endif
                                </td>
                                <td>
                                    @if($user->isArchived())
                                        <span class="badge-error text-xs">Archiviert</span>
                                    @elseif($user->invited_at)
                                        <span class="badge-success text-xs">Eingeladen</span>
                                    @else
                                        <span class="badge-neutral text-xs">Nicht eingeladen</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Role Assignment --}}
                                        <div class="relative">
                                            <button @click="showRoles = !showRoles; showActions = false" class="btn-secondary btn-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                Rollen
                                            </button>
                                            <div x-show="showRoles"
                                                 x-transition
                                                 @click.away="showRoles = false"
                                                 class="dropdown-menu right-0 mt-1 w-72 z-50">
                                                <div class="dropdown-header">Rollen zuweisen</div>
                                                <form method="POST" action="{{ route('admin.users.updateRoles', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="p-3 space-y-2">
                                                        @foreach($roles as $role)
                                                            <label class="flex items-center gap-2 py-1 px-2 rounded hover:bg-surface-50 cursor-pointer">
                                                                <input type="checkbox"
                                                                       name="roles[]"
                                                                       value="{{ $role->id }}"
                                                                       class="checkbox-field"
                                                                       {{ $user->roles->contains('id', $role->id) ? 'checked' : '' }}>
                                                                <div>
                                                                    <span class="text-sm font-medium text-brand-dark">{{ $role->name }}</span>
                                                                    @if($role->description)
                                                                        <span class="text-xs text-surface-400 block">{{ $role->description }}</span>
                                                                    @endif
                                                                </div>
                                                            </label>
                                                        @endforeach
                                                        <button type="submit" class="btn-primary btn-sm w-full mt-2">Speichern</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Managed Teams (for managers) --}}
                                        @if($user->hasRole(['people_manager', 'head_of']))
                                        <div class="relative">
                                            <button @click="showTeams = !showTeams; showRoles = false; showActions = false" class="btn-secondary btn-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                Teams
                                                @if($user->managedTeams->isNotEmpty())
                                                    <span class="ml-1 bg-brand-primary/20 text-brand-primary text-xs rounded-full px-1.5">{{ $user->managedTeams->count() }}</span>
                                                @endif
                                            </button>
                                            <div x-show="showTeams"
                                                 x-transition
                                                 @click.away="showTeams = false"
                                                 class="dropdown-menu right-0 mt-1 w-72 z-50">
                                                <div class="dropdown-header">Betreute Teams</div>
                                                <form method="POST" action="{{ route('admin.users.updateManagedTeams', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <div class="p-3 space-y-2 max-h-48 overflow-y-auto">
                                                        @foreach($teams as $team)
                                                            <label class="flex items-center gap-2 py-1 px-2 rounded hover:bg-surface-50 cursor-pointer">
                                                                <input type="checkbox"
                                                                       name="managed_teams[]"
                                                                       value="{{ $team->id }}"
                                                                       class="checkbox-field"
                                                                       {{ $user->managedTeams->contains('id', $team->id) ? 'checked' : '' }}>
                                                                <span class="text-sm text-brand-dark">{{ $team->name }}</span>
                                                            </label>
                                                        @endforeach
                                                        @if($teams->isEmpty())
                                                            <p class="text-xs text-surface-400 py-2">Noch keine Teams vorhanden.</p>
                                                        @endif
                                                        <button type="submit" class="btn-primary btn-sm w-full mt-2">Speichern</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        @endif

                                        {{-- Actions Dropdown --}}
                                        <div class="relative">
                                            <button @click="showActions = !showActions; showRoles = false; showTeams = false" class="btn-ghost btn-xs">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                                </svg>
                                            </button>
                                            <div x-show="showActions"
                                                 x-transition
                                                 @click.away="showActions = false"
                                                 class="dropdown-menu right-0 mt-1 w-56 z-50">

                                                @if(!$user->isArchived())
                                                    {{-- Send Invitation --}}
                                                    <form method="POST" action="{{ route('admin.users.invite', $user) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item w-full text-left flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                            </svg>
                                                            Einladung senden
                                                            @if($user->invited_at)
                                                                <span class="text-xs text-surface-400 ml-auto">{{ $user->invited_at->format('d.m.') }}</span>
                                                            @endif
                                                        </button>
                                                    </form>

                                                    {{-- Reset Password --}}
                                                    <form method="POST" action="{{ route('admin.users.resetPassword', $user) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item w-full text-left flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                                            </svg>
                                                            Passwort zurücksetzen
                                                        </button>
                                                    </form>

                                                    <div class="border-t border-surface-100 my-1"></div>

                                                    {{-- Archive --}}
                                                    @if($user->id !== auth()->id())
                                                        <form method="POST" action="{{ route('admin.users.archive', $user) }}"
                                                              onsubmit="return confirm('{{ $user->name }} wirklich archivieren? Der Zugang wird sofort gesperrt.')">
                                                            @csrf
                                                            <button type="submit" class="dropdown-item w-full text-left flex items-center gap-2 text-ui-error">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                                                </svg>
                                                                Archivieren
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    {{-- Restore --}}
                                                    <form method="POST" action="{{ route('admin.users.restore', $user) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item w-full text-left flex items-center gap-2 text-ui-success">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                            </svg>
                                                            Wiederherstellen
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-8">
                                    <div class="empty-state">
                                        <svg class="w-12 h-12 mx-auto text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        <p class="mt-2 text-surface-500">
                                            @if($tab === 'archived')
                                                Keine archivierten Nutzer vorhanden.
                                            @else
                                                Keine Nutzer gefunden.
                                            @endif
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($users->hasPages())
            <div class="card-tool-footer">
                {{ $users->links() }}
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
