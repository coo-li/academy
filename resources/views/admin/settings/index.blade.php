<x-app-layout>
    @section('page-title', 'Einstellungen')

    <div class="space-y-6">
        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Page Header --}}
        <div>
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Einstellungen</h1>
            <p class="text-surface-500 mt-1">Integrationen & Systemstatus</p>
        </div>

        {{-- User Stats --}}
        <div class="card-tool">
            <div class="card-tool-header">
                <h2 class="font-semibold">Benutzer-Übersicht</h2>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary btn-xs">Nutzerverwaltung</a>
            </div>
            <div class="card-tool-body">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-brand-dark">{{ $userStats['total'] }}</div>
                        <div class="text-xs text-surface-500 uppercase tracking-wide">Gesamt</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-ui-success">{{ $userStats['active'] }}</div>
                        <div class="text-xs text-surface-500 uppercase tracking-wide">Aktiv</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-surface-400">{{ $userStats['archived'] }}</div>
                        <div class="text-xs text-surface-500 uppercase tracking-wide">Archiviert</div>
                    </div>
                    @foreach($userStats as $label => $count)
                        @if(!in_array($label, ['total', 'active', 'archived']))
                        <div class="text-center">
                            <div class="text-2xl font-bold text-surface-600">{{ $count }}</div>
                            <div class="text-xs text-surface-500 uppercase tracking-wide">{{ $label }}</div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Integrations --}}
        <div>
            <h2 class="text-2xl font-bold text-brand-dark mb-4">Integrationen</h2>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                @foreach($integrations as $key => $integration)
                <div class="card-tool">
                    <div class="card-tool-header">
                        <div class="flex items-center gap-2">
                            @if($key === 'personio')
                            <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            @elseif($key === 'asana')
                            <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            @else
                            <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            @endif
                            <h3 class="font-semibold text-brand-dark">{{ $integration['name'] }}</h3>
                        </div>
                        @if($integration['configured'])
                            <span class="badge-success">Verbunden</span>
                        @else
                            <span class="badge-error">Nicht konfiguriert</span>
                        @endif
                    </div>
                    <div class="card-tool-body">
                        <p class="text-sm text-surface-500 mb-4">{{ $integration['description'] }}</p>

                        {{-- ENV Status --}}
                        <div class="space-y-2 mb-4">
                            <div class="text-xs text-surface-400 uppercase tracking-wide font-medium">Konfiguration</div>
                            @foreach($integration['env_keys'] as $envKey)
                            <div class="flex items-center justify-between">
                                @if(is_array($envKey))
                                    <code class="text-xs bg-surface-100 px-2 py-0.5 rounded font-mono">{{ $envKey['label'] }}</code>
                                    @if($envKey['ok'])
                                        <svg class="w-4 h-4 text-ui-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-ui-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @endif
                                @else
                                    <code class="text-xs bg-surface-100 px-2 py-0.5 rounded font-mono">{{ $envKey }}</code>
                                    @if(filled(env($envKey)) && env($envKey) !== 'DEIN_CLIENT_ID_HIER' && env($envKey) !== 'DEIN_CLIENT_SECRET_HIER')
                                        <svg class="w-4 h-4 text-ui-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-ui-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @endif
                                @endif
                            </div>
                            @endforeach
                        </div>

                        {{-- Stats --}}
                        @if(!empty($integration['stats']))
                        <div class="space-y-2 mb-4">
                            <div class="text-xs text-surface-400 uppercase tracking-wide font-medium">Statistiken</div>
                            @foreach($integration['stats'] as $label => $value)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-surface-500">{{ $label }}</span>
                                <span class="font-medium text-brand-dark">{{ $value }}</span>
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Last Sync --}}
                        @if($integration['last_sync'])
                        @php $sync = $integration['last_sync']; @endphp
                        <div class="border-t border-surface-200 pt-3 mt-3">
                            <div class="flex items-center gap-2 text-xs">
                                @if($sync->isSuccess())
                                    <span class="w-2 h-2 rounded-full bg-ui-success"></span>
                                    <span class="text-surface-500">Letzter Sync: {{ $sync->started_at->diffForHumans() }}</span>
                                @else
                                    <span class="w-2 h-2 rounded-full bg-ui-error"></span>
                                    <span class="text-ui-error">Sync fehlgeschlagen: {{ $sync->started_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>

                    @if($integration['admin_url'])
                    <div class="card-tool-footer">
                        <a href="{{ $integration['admin_url'] }}" class="btn-secondary btn-sm w-full text-center">
                            {{ $integration['admin_label'] }}
                        </a>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Stundensätze Verwaltung --}}
        @livewire('admin.career-level-rates-manager')

        {{-- Sollstunden pro Mitarbeiter --}}
        @livewire('admin.user-target-hours-manager')

        {{-- System Info --}}
        <div class="card-tool">
            <div class="card-tool-header">
                <h2 class="font-semibold">System-Info</h2>
            </div>
            <div class="card-tool-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-surface-500">Laravel</span>
                            <span class="font-mono text-brand-dark">{{ app()->version() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-surface-500">PHP</span>
                            <span class="font-mono text-brand-dark">{{ PHP_VERSION }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-surface-500">Umgebung</span>
                            <span class="badge-{{ app()->environment('production') ? 'error' : 'info' }}">{{ app()->environment() }}</span>
                        </div>
                    </div>
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-surface-500">Datenbank</span>
                            <span class="font-mono text-brand-dark">{{ config('database.default') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-surface-500">Cache</span>
                            <span class="font-mono text-brand-dark">{{ config('cache.default') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-surface-500">Queue</span>
                            <span class="font-mono text-brand-dark">{{ config('queue.default') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Artisan Commands Reference --}}
        <div class="card-tool">
            <div class="card-tool-header">
                <h2 class="font-semibold">Verfügbare Befehle</h2>
            </div>
            <div class="card-tool-body">
                <div class="overflow-x-auto">
                    <table class="table-tool table-tool-compact">
                        <thead>
                            <tr>
                                <th>Befehl</th>
                                <th>Beschreibung</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code class="text-xs bg-surface-100 px-2 py-0.5 rounded font-mono">php artisan academy:sync-personio</code></td>
                                <td class="text-sm text-surface-500">Mitarbeiter aus Personio synchronisieren</td>
                            </tr>
                            <tr>
                                <td><code class="text-xs bg-surface-100 px-2 py-0.5 rounded font-mono">php artisan academy:sync-personio --dry-run</code></td>
                                <td class="text-sm text-surface-500">Sync nur simulieren (keine Änderungen)</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
