<x-app-layout>
    @section('page-title', 'Dashboard')

    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Willkommen, {{ Auth::user()->name }}!</h1>
                <p class="text-surface-500 mt-1">Dein Lernfortschritt auf einen Blick.</p>
            </div>
            @if(Auth::user()->isManager())
            <div class="flex items-center gap-2">
                <a href="#" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Berichte
                </a>
                <a href="#" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Modul erstellen
                </a>
            </div>
            @endif
        </div>

        {{-- Stats --}}
        @php
            $user = Auth::user();
            $enrollmentCount = $user->enrollments()->count();
            $completedCount = $user->enrollments()->where('status', 'completed')->count();
            $inProgressCount = $user->enrollments()->where('status', 'in_progress')->count();
            $percentage = $enrollmentCount > 0 ? round(($completedCount / $enrollmentCount) * 100) : 0;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="stat-card">
                <div class="stat-label">Eingeschriebene Module</div>
                <div class="stat-value">{{ $enrollmentCount }}</div>
                <div class="help-text mt-2">Pflicht- und Wahlmodule</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Abgeschlossen</div>
                <div class="stat-value text-ui-success">{{ $completedCount }}</div>
                <div class="stat-trend-up mt-2" style="{{ $completedCount === 0 ? 'display:none' : '' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>{{ $percentage }}% Fortschritt</span>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-label">In Bearbeitung</div>
                <div class="stat-value text-brand-primary">{{ $inProgressCount }}</div>
                <div class="help-text mt-2">Aktuell aktive Module</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Gesamtfortschritt</div>
                <div class="stat-value">{{ $percentage }}%</div>
                <div class="mt-3">
                    <div class="progress-bar">
                        <div class="progress-bar-fill" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Main Content --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Karrierepfade --}}
            <div class="lg:col-span-2">
                <x-card title="Karrierepfade">
                    @php $paths = \App\Models\CareerPath::with('levels.modules')->get(); @endphp

                    @forelse($paths as $path)
                    <div class="{{ !$loop->first ? 'mt-6 pt-6 border-t border-surface-200' : '' }}">
                        <h3 class="font-semibold text-brand-dark text-lg">{{ $path->name }}</h3>
                        <p class="text-sm text-surface-500 mt-1">{{ $path->description }}</p>

                        <div class="mt-4 space-y-3">
                            @foreach($path->levels as $level)
                            <div class="panel-compact">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold
                                            {{ $level->level_number === 1 ? 'bg-ui-success-light text-ui-success-dark' : '' }}
                                            {{ $level->level_number === 2 ? 'bg-ui-info-light text-ui-info-dark' : '' }}
                                            {{ $level->level_number >= 3 ? 'bg-ui-warning-light text-ui-warning-dark' : '' }}">
                                            {{ $level->level_number }}
                                        </div>
                                        <div>
                                            <div class="font-medium text-brand-dark">{{ $level->title }}</div>
                                            <div class="text-xs text-surface-500">{{ $level->modules->count() }} Module</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @foreach($level->modules as $module)
                                        @if($module->method)
                                        <span class="badge-primary hidden md:inline-flex">
                                            {{ $module->method->name }}
                                        </span>
                                        @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @empty
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <div class="empty-state-title">Noch keine Karrierepfade</div>
                        <div class="empty-state-description">Karrierepfade werden vom Admin in der Struktur-Verwaltung angelegt.</div>
                    </div>
                    @endforelse
                </x-card>
            </div>

            {{-- Sidebar: Quick Actions + Info --}}
            <div class="space-y-6">
                {{-- Rolle --}}
                <div class="card-tool p-4">
                    <div class="flex items-center gap-3">
                        <div class="avatar-lg">
                            <span>{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                        </div>
                        <div>
                            <div class="font-semibold text-brand-dark">{{ Auth::user()->name }}</div>
                            <div class="text-sm text-surface-500">{{ Auth::user()->email }}</div>
                            @php
                                $roleBadgeMap = [
                                    'admin' => 'badge-error',
                                    'people_manager' => 'badge-warning',
                                    'head_of' => 'badge-warning',
                                    'trainer' => 'badge-info',
                                    'mitarbeitender' => 'badge-success',
                                ];
                            @endphp
                            <div class="flex flex-wrap gap-1 mt-1">
                                @forelse(Auth::user()->roles as $role)
                                    <span class="{{ $roleBadgeMap[$role->slug] ?? 'badge-neutral' }}">{{ $role->name }}</span>
                                @empty
                                    <span class="badge-neutral">Mitarbeitender</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Quick Actions --}}
                <x-card title="Schnellzugriff">
                    <div class="space-y-2">
                        <a href="#" class="sidebar-link">
                            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                            Modulkatalog öffnen
                        </a>
                        <a href="#" class="sidebar-link">
                            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                            </svg>
                            Digitale Mappe
                        </a>
                        <a href="#" class="sidebar-link">
                            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            Nächste Termine
                        </a>
                        <a href="{{ route('profile.edit') }}" class="sidebar-link">
                            <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Profil bearbeiten
                        </a>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
