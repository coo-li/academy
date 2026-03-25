<x-app-layout>
    @section('page-title', $path->name . ' – Struktur-Verwaltung')

    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm">
            <a href="{{ route('admin.modules.index') }}" class="text-surface-500 hover:text-brand-primary transition-colors">Struktur-Verwaltung</a>
            <svg class="w-4 h-4 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span class="text-brand-dark font-medium">{{ $path->name }}</span>
        </nav>

        {{-- Path Header --}}
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-start gap-4">
                <div class="w-14 h-14 rounded-xl bg-brand-primary-light flex items-center justify-center text-3xl shrink-0">
                    {{ $path->emoji ?? '📋' }}
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-brand-dark">{{ $path->name }}</h1>
                    @if($path->description)
                    <p class="text-surface-500 mt-1 max-w-2xl">{{ $path->description }}</p>
                    @endif
                    <div class="flex items-center gap-3 mt-2">
                        <span class="badge-info">Sequenziell</span>
                        <span class="text-xs text-surface-400">
                            {{ $path->levels->count() }} {{ $path->levels->count() === 1 ? 'Stufe' : 'Stufen' }} &middot;
                            {{ $path->levels->sum(fn ($l) => $l->modules->count()) }} Module
                        </span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('admin.modules.create') }}" class="btn-primary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Modul hinzufügen
                </a>
                <a href="{{ route('admin.paths.edit', $path) }}" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Pfad bearbeiten
                </a>
                <form method="POST" action="{{ route('admin.paths.destroy', $path) }}"
                      onsubmit="return confirm('Karrierepfad &quot;{{ $path->name }}&quot; mit allen Stufen und Modulen wirklich löschen?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- Levels with Modules --}}
        @foreach($path->levels as $level)
        <x-card>
            <x-slot:header>
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div class="w-8 h-8 rounded-full bg-brand-primary-light flex items-center justify-center text-sm font-bold text-brand-primary shrink-0">
                        {{ $level->level_number }}
                    </div>
                    <div class="min-w-0">
                        <h2 class="font-semibold text-brand-dark truncate">{{ $level->title }}</h2>
                        @if($level->description)
                        <p class="text-xs text-surface-500 truncate">{{ $level->description }}</p>
                        @endif
                    </div>
                    <span class="badge-neutral shrink-0">{{ $level->modules->count() }} {{ $level->modules->count() === 1 ? 'Modul' : 'Module' }}</span>
                </div>
            </x-slot:header>

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
            <div class="text-center py-6">
                <p class="text-sm text-surface-400 italic">Noch keine Module in dieser Stufe.</p>
            </div>
            @endif
        </x-card>

        @if(!$loop->last)
        <div class="flex justify-center -my-2">
            <svg class="w-6 h-6 text-surface-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
            </svg>
        </div>
        @endif
        @endforeach

        @if($path->levels->isEmpty())
        <div class="bg-white rounded-2xl border border-dashed border-surface-300 p-8 text-center">
            <p class="text-surface-500 mb-3">Dieser Karrierepfad hat noch keine Stufen.</p>
            <a href="{{ route('admin.paths.edit', $path) }}" class="btn-primary btn-sm">Stufen hinzufügen</a>
        </div>
        @endif
    </div>
</x-app-layout>
