<x-app-layout>
    @section('page-title', 'Struktur-Orga')

    <div class="space-y-8">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Struktur-Orga</h1>
                <p class="text-surface-500 mt-1">Karrierepfade und Skill-Gruppen verwalten.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.paths.create') }}" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Karrierepfad
                </a>
                <a href="{{ route('admin.skill-categories.index') }}" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Skill-Gruppe
                </a>
                <a href="{{ route('admin.modules.create') }}" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Modul erstellen
                </a>
            </div>
        </div>

        {{-- Method Filter Results --}}
        @if($methodFilter && $filteredModules)
        <div>
            <div class="flex items-center gap-3 mb-4">
                <h2 class="text-xl font-bold text-brand-dark">Module mit Methode: {{ $methodFilter->name }}</h2>
                <span class="badge-info">{{ $filteredModules->count() }}</span>
                <a href="{{ route('admin.modules.index') }}" class="btn-secondary btn-xs ml-auto">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    Filter entfernen
                </a>
            </div>

            @if($filteredModules->isNotEmpty())
            <x-card>
                <div class="overflow-x-auto">
                    <table class="table-tool table-tool-compact">
                        <thead>
                            <tr>
                                <th>Modul</th>
                                <th>Karrierepfad / Skill-Gruppe</th>
                                <th>Pflicht</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($filteredModules as $module)
                            <tr>
                                <td>
                                    <div class="font-medium text-brand-dark">{{ $module->title }}</div>
                                    @if($module->description)
                                    <div class="text-xs text-surface-500 truncate max-w-[300px]">{{ $module->description }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($module->careerLevel?->careerPath)
                                        <a href="{{ route('admin.paths.show', $module->careerLevel->careerPath) }}" class="badge-primary hover:opacity-80 transition-opacity">
                                            {{ $module->careerLevel->careerPath->name }} &ndash; {{ $module->careerLevel->title }}
                                        </a>
                                    @elseif($module->skillCategory)
                                        <a href="{{ route('admin.skill-categories.show', $module->skillCategory) }}" class="badge-neutral hover:opacity-80 transition-opacity">
                                            {{ $module->skillCategory->name }}
                                        </a>
                                    @else
                                        <span class="text-xs text-surface-400">Nicht zugeordnet</span>
                                    @endif
                                </td>
                                <td>
                                    @if($module->is_mandatory)
                                    <span class="badge-error">Pflicht</span>
                                    @else
                                    <span class="badge-neutral">Optional</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.modules.edit', $module) }}" class="btn-secondary btn-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Bearbeiten
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
            @else
            <div class="bg-white rounded-2xl border border-dashed border-surface-300 p-8 text-center">
                <p class="text-surface-500">Keine Module mit dieser Methode vorhanden.</p>
            </div>
            @endif
        </div>
        @endif

        {{-- Karrierepfade --}}
        <div>
            <div class="flex items-center gap-3 mb-4">
                <h2 class="text-xl font-bold text-brand-dark">Karrierepfade</h2>
                <span class="badge-info">Sequenziell</span>
            </div>

            @if($paths->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($paths as $path)
                <a href="{{ route('admin.paths.show', $path) }}"
                   class="group block bg-white rounded-2xl border border-surface-200 p-5 hover:border-brand-primary/40 hover:shadow-md transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-brand-primary-light flex items-center justify-center text-2xl shrink-0">
                            {{ $path->emoji ?? '📋' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-brand-dark group-hover:text-brand-primary transition-colors truncate">{{ $path->name }}</h3>
                            @if($path->description)
                            <p class="text-sm text-surface-500 mt-0.5 line-clamp-2">{{ $path->description }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-3">
                                <span class="text-xs text-surface-400">
                                    <span class="font-medium text-surface-600">{{ $path->levels_count }}</span> {{ $path->levels_count === 1 ? 'Stufe' : 'Stufen' }}
                                </span>
                                <span class="text-surface-200">&middot;</span>
                                <span class="text-xs text-surface-400">
                                    <span class="font-medium text-surface-600">{{ $path->modules_count }}</span> {{ $path->modules_count === 1 ? 'Modul' : 'Module' }}
                                </span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-surface-300 group-hover:text-brand-primary transition-colors shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="bg-white rounded-2xl border border-dashed border-surface-300 p-8 text-center">
                <svg class="w-10 h-10 text-surface-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <p class="text-surface-500 mb-3">Noch keine Karrierepfade vorhanden.</p>
                <a href="{{ route('admin.paths.create') }}" class="btn-primary btn-sm">Karrierepfad erstellen</a>
            </div>
            @endif
        </div>

        {{-- Skill-Gruppen --}}
        <div>
            <div class="flex items-center gap-3 mb-4">
                <h2 class="text-xl font-bold text-brand-dark">Skill-Gruppen</h2>
                <span class="badge-neutral">Flexibel</span>
            </div>

            @if($skillGroups->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($skillGroups as $group)
                <a href="{{ route('admin.skill-categories.show', $group) }}"
                   class="group block bg-white rounded-2xl border border-surface-200 p-5 hover:border-brand-primary/40 hover:shadow-md transition-all duration-200">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-xl bg-surface-100 flex items-center justify-center text-2xl shrink-0">
                            {{ $group->emoji ?? '🧩' }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-brand-dark group-hover:text-brand-primary transition-colors truncate">{{ $group->name }}</h3>
                            @if($group->description)
                            <p class="text-sm text-surface-500 mt-0.5 line-clamp-2">{{ $group->description }}</p>
                            @endif
                            <div class="flex items-center gap-3 mt-3">
                                <span class="text-xs text-surface-400">
                                    <span class="font-medium text-surface-600">{{ $group->modules_count }}</span> {{ $group->modules_count === 1 ? 'Modul' : 'Module' }}
                                </span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-surface-300 group-hover:text-brand-primary transition-colors shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </a>
                @endforeach
            </div>
            @else
            <div class="bg-white rounded-2xl border border-dashed border-surface-300 p-8 text-center">
                <svg class="w-10 h-10 text-surface-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                <p class="text-surface-500 mb-3">Noch keine Skill-Gruppen vorhanden.</p>
                <a href="{{ route('admin.skill-categories.index') }}" class="btn-primary btn-sm">Skill-Gruppe erstellen</a>
            </div>
            @endif
        </div>

        {{-- Nicht zugeordnete Module --}}
        @if($unassignedModules->isNotEmpty())
        <div>
            <div class="flex items-center gap-3 mb-4">
                <h2 class="text-xl font-bold text-brand-dark">Nicht zugeordnete Module</h2>
                <span class="badge-warning">{{ $unassignedModules->count() }}</span>
            </div>

            <x-card>
                <p class="text-sm text-surface-500 mb-4">Diese Module sind weder einem Karrierepfad noch einer Skill-Gruppe zugeordnet.</p>
                <div class="overflow-x-auto">
                    <table class="table-tool table-tool-compact">
                        <thead>
                            <tr>
                                <th>Modul</th>
                                <th>Methode</th>
                                <th>Pflicht</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($unassignedModules as $module)
                            <tr>
                                <td>
                                    <div class="font-medium text-brand-dark">{{ $module->title }}</div>
                                    @if($module->description)
                                    <div class="text-xs text-surface-500 truncate max-w-[300px]">{{ $module->description }}</div>
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
                                    @if($module->is_mandatory)
                                    <span class="badge-error">Pflicht</span>
                                    @else
                                    <span class="badge-neutral">Optional</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.modules.edit', $module) }}" class="btn-secondary btn-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Bearbeiten
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>
        @endif
    </div>
</x-app-layout>
