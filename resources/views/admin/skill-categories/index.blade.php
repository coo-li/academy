<x-app-layout>
    @section('page-title', 'Skill-Kategorien')

    <div class="max-w-3xl mx-auto space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Skill-Kategorien</h1>
                <p class="text-surface-500 mt-1">Übergreifende Kategorien für Schulungsmodule verwalten.</p>
            </div>
        </div>

        {{-- New Category Form --}}
        <x-card title="Neue Kategorie anlegen">
            <form method="POST" action="{{ route('admin.skill-categories.store') }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <div class="flex-1">
                    <input type="text" name="name" class="input-field" required
                           placeholder="Name der Kategorie" value="{{ old('name') }}">
                    @error('name') <p class="error-text">{{ $message }}</p> @enderror
                </div>
                <div class="flex-1">
                    <input type="text" name="description" class="input-field"
                           placeholder="Beschreibung (optional)" value="{{ old('description') }}">
                </div>
                <button type="submit" class="btn-primary whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Anlegen
                </button>
            </form>
        </x-card>

        {{-- Category List --}}
        <x-card>
            <x-slot:header>
                <h2 class="font-semibold text-brand-dark">Alle Kategorien</h2>
                <span class="badge-info">{{ $categories->count() }}</span>
            </x-slot:header>

            @if($categories->isNotEmpty())
            <div class="space-y-0 divide-y divide-surface-200">
                @foreach($categories as $category)
                <div x-data="{ editing: false }" class="py-3 first:pt-0 last:pb-0">
                    {{-- Display Mode --}}
                    <div x-show="!editing" class="flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-brand-dark">{{ $category->name }}</div>
                            @if($category->description)
                            <div class="text-sm text-surface-500">{{ $category->description }}</div>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            <span class="badge-neutral">{{ $category->modules_count }} {{ $category->modules_count === 1 ? 'Modul' : 'Module' }}</span>
                            <div class="flex items-center gap-1">
                                <button @click="editing = true" class="btn-secondary btn-xs" title="Bearbeiten">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('admin.skill-categories.destroy', $category) }}" class="inline"
                                      onsubmit="return confirm('Kategorie &quot;{{ $category->name }}&quot; wirklich löschen?{{ $category->modules_count > 0 ? ' ' . $category->modules_count . ' Module werden entkoppelt.' : '' }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger btn-xs" title="Löschen">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Edit Mode --}}
                    <form x-show="editing" x-cloak method="POST"
                          action="{{ route('admin.skill-categories.update', $category) }}"
                          class="flex flex-col sm:flex-row gap-3">
                        @csrf
                        @method('PUT')
                        <div class="flex-1">
                            <input type="text" name="name" class="input-field" required
                                   value="{{ $category->name }}" placeholder="Name">
                        </div>
                        <div class="flex-1">
                            <input type="text" name="description" class="input-field"
                                   value="{{ $category->description }}" placeholder="Beschreibung (optional)">
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="submit" class="btn-primary btn-sm">Speichern</button>
                            <button type="button" @click="editing = false" class="btn-secondary btn-sm">Abbrechen</button>
                        </div>
                    </form>
                </div>
                @endforeach
            </div>
            @else
            <div class="empty-state">
                <div class="empty-state-title">Keine Kategorien vorhanden</div>
                <div class="empty-state-description">Erstelle oben die erste Skill-Kategorie.</div>
            </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
