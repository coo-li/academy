<x-app-layout>
    @section('page-title', 'Struktur-Verwaltung')

    <div class="space-y-6">
        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Struktur-Verwaltung</h1>
                <p class="text-surface-500 mt-1">Karrierepfade, Levels und Module verwalten.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.paths.create') }}" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Karrierepfad
                </a>
                <a href="{{ route('admin.modules.create') }}" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Modul erstellen
                </a>
            </div>
        </div>

        {{-- Global Modules --}}
        @if($globalModules->isNotEmpty())
        <x-card>
            <x-slot:header>
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <h2 class="font-semibold text-brand-dark">Allgemeine Module</h2>
                    <span class="badge-info">{{ $globalModules->count() }} Module</span>
                </div>
            </x-slot:header>

            <p class="text-sm text-surface-500 mb-4">Module ohne Karrierestufe &ndash; k&ouml;nnen &uuml;ber die Modul-Zuordnung direkt an Mitarbeitende vergeben werden.</p>

            <div class="overflow-x-auto">
                <table class="table-tool table-tool-compact">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Modul</th>
                            <th>Kategorie</th>
                            <th>Methode</th>
                            <th>Accountable</th>
                            <th>Pflicht</th>
                            <th>Quiz</th>
                            <th class="text-right">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($globalModules as $module)
                        <tr>
                            <td class="text-surface-400">{{ $module->sort_order }}</td>
                            <td>
                                <div class="font-medium text-brand-dark">{{ $module->title }}</div>
                                @if($module->description)
                                <div class="text-xs text-surface-500 truncate max-w-[300px]">{{ $module->description }}</div>
                                @endif
                            </td>
                            <td>
                                @if($module->skillCategory)
                                <span class="text-xs text-surface-500">{{ $module->skillCategory->name }}</span>
                                @else
                                <span class="text-xs text-surface-300">&ndash;</span>
                                @endif
                            </td>
                            <td>
                                @if($module->method)
                                <span class="badge-primary">{{ $module->method->name }}</span>
                                @else
                                <span class="text-xs text-surface-300">&ndash;</span>
                                @endif
                            </td>
                            <td>
                                @if($module->accountable_type === 'user' && $module->accountableUser)
                                <span class="text-xs text-surface-500">{{ $module->accountableUser->name }}</span>
                                @elseif($module->accountable_type === 'head_of')
                                <span class="badge-info">Head of</span>
                                @else
                                <span class="text-xs text-surface-300">&ndash;</span>
                                @endif
                            </td>
                            <td>
                                @if($module->is_mandatory)
                                <span class="badge-error">Pflicht</span>
                                @else
                                <span class="badge-neutral">Optional</span>
                                @endif
                            </td>
                            <td>
                                @if($module->quiz)
                                <span class="badge-success">{{ count($module->quiz->questions ?? []) }} Fragen</span>
                                @else
                                <span class="badge-neutral">Kein Quiz</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="{{ route('admin.modules.edit', $module) }}" class="btn-secondary btn-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" class="inline"
                                          onsubmit="return confirm('Modul wirklich l&ouml;schen?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
        @endif

        {{-- Career Paths --}}
        @forelse($paths as $path)
        <x-card>
            <x-slot:header>
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <h2 class="font-semibold text-brand-dark">{{ $path->name }}</h2>
                    <span class="badge-info">{{ $path->levels->sum(fn ($l) => $l->modules->count()) }} Module</span>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('admin.paths.edit', $path) }}" class="btn-secondary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Bearbeiten
                    </a>
                    <form method="POST" action="{{ route('admin.paths.destroy', $path) }}"
                          onsubmit="return confirm('Karrierepfad &quot;{{ $path->name }}&quot; mit allen Stufen und Modulen wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-danger btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Pfad löschen
                        </button>
                    </form>
                </div>
            </x-slot:header>

            @if($path->description)
            <p class="text-sm text-surface-500 mb-4">{{ $path->description }}</p>
            @endif

            @foreach($path->levels as $level)
            <div class="{{ !$loop->first ? 'mt-6 pt-6 border-t border-surface-200' : '' }}">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-8 h-8 rounded-full bg-brand-primary-light flex items-center justify-center text-sm font-bold text-brand-primary">
                        {{ $level->level_number }}
                    </div>
                    <div>
                        <span class="font-medium text-brand-dark">{{ $level->title }}</span>
                        @if($level->description)
                        <span class="text-xs text-surface-500 ml-2">{{ $level->description }}</span>
                        @endif
                    </div>
                </div>

                @if($level->modules->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="table-tool table-tool-compact">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Modul</th>
                                <th>Kategorie</th>
                                <th>Methode</th>
                                <th>Accountable</th>
                                <th>Pflicht</th>
                                <th>Quiz</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($level->modules as $module)
                            <tr>
                                <td class="text-surface-400">{{ $module->sort_order }}</td>
                                <td>
                                    <div class="font-medium text-brand-dark">{{ $module->title }}</div>
                                    @if($module->description)
                                    <div class="text-xs text-surface-500 truncate max-w-[300px]">{{ $module->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($module->skillCategory)
                                    <span class="text-xs text-surface-500">{{ $module->skillCategory->name }}</span>
                                    @else
                                    <span class="text-xs text-surface-300">–</span>
                                    @endif
                                </td>
                                <td>
                                    @if($module->method)
                                    <span class="badge-primary">{{ $module->method->name }}</span>
                                    @else
                                    <span class="text-xs text-surface-300">&ndash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if($module->accountable_type === 'user' && $module->accountableUser)
                                    <span class="text-xs text-surface-500">{{ $module->accountableUser->name }}</span>
                                    @elseif($module->accountable_type === 'head_of')
                                    <span class="badge-info">Head of</span>
                                    @else
                                    <span class="text-xs text-surface-300">&ndash;</span>
                                    @endif
                                </td>
                                <td>
                                    @if($module->is_mandatory)
                                    <span class="badge-error">Pflicht</span>
                                    @else
                                    <span class="badge-neutral">Optional</span>
                                    @endif
                                </td>
                                <td>
                                    @if($module->quiz)
                                    <span class="badge-success">{{ count($module->quiz->questions ?? []) }} Fragen</span>
                                    @else
                                    <span class="badge-neutral">Kein Quiz</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('admin.modules.edit', $module) }}" class="btn-secondary btn-xs">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" class="inline"
                                              onsubmit="return confirm('Modul wirklich löschen?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-danger btn-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-sm text-surface-400 italic pl-11">Noch keine Module in dieser Stufe.</p>
                @endif
            </div>
            @endforeach
        </x-card>
        @empty
        <div class="card-tool">
            <div class="card-tool-body">
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <div class="empty-state-title">Keine Karrierepfade vorhanden</div>
                    <div class="empty-state-description">Erstelle zuerst einen Karrierepfad, dann kannst du Module hinzufügen.</div>
                    <a href="{{ route('admin.paths.create') }}" class="btn-primary mt-4">Karrierepfad erstellen</a>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</x-app-layout>
