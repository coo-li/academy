<x-app-layout>
    @section('page-title', 'Karrierepfad bearbeiten')

    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('admin.modules.index') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Zurück zur Übersicht
            </a>
            <h1 class="text-3xl font-bold text-brand-dark">Karrierepfad bearbeiten</h1>
            <p class="text-surface-500 mt-1">Bearbeite den Pfad und füge neue Karrierestufen hinzu.</p>
        </div>

        <x-card title="Karrierepfad">
            <form method="POST" action="{{ route('admin.paths.update', $path) }}"
                  x-data="pathEditor()" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="label label-required">Name des Pfades</label>
                    <input type="text" name="name" class="input-field" required placeholder="z.B. SEO Specialist" value="{{ old('name', $path->name) }}">
                    @error('name') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Beschreibung</label>
                    <textarea name="description" class="input-field" rows="2" placeholder="Kurze Beschreibung des Karrierepfades...">{{ old('description', $path->description) }}</textarea>
                </div>

                <div>
                    <label class="label label-required">Karrierestufen</label>
                    <p class="help-text mb-3">Definiere die Stufen von unten (Einstieg) nach oben (Experte). Bestehende Stufen mit Modulen können nicht entfernt werden.</p>

                    <div class="space-y-3">
                        <template x-for="(level, index) in levels" :key="level._key">
                            <div class="panel-compact flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-brand-primary-light flex items-center justify-center text-sm font-bold text-brand-primary flex-shrink-0 mt-1" x-text="index + 1"></div>
                                <div class="flex-1 space-y-2">
                                    <input type="hidden" :name="'levels[' + index + '][id]'" :value="level.id || ''">
                                    <input type="text" :name="'levels[' + index + '][title]'" class="input-field" required
                                           placeholder="z.B. Junior SEO" x-model="level.title">
                                    <input type="text" :name="'levels[' + index + '][description]'" class="input-field"
                                           placeholder="Optionale Beschreibung..." x-model="level.description">
                                    <template x-if="level.module_count > 0">
                                        <p class="text-xs text-surface-400">
                                            <span x-text="level.module_count"></span> Modul(e) zugeordnet
                                        </p>
                                    </template>
                                </div>
                                <button type="button"
                                        @click="removeLevel(index)"
                                        class="btn-danger btn-xs mt-1"
                                        x-show="levels.length > 1 && !level.module_count"
                                        :title="level.module_count ? 'Stufe hat Module — zuerst Module entfernen' : 'Stufe entfernen'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addLevel()" class="btn-ghost btn-sm mt-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Stufe hinzufügen
                    </button>
                </div>

                @error('levels') <p class="error-text">{{ $message }}</p> @enderror

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-200">
                    <a href="{{ route('admin.modules.index') }}" class="btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Änderungen speichern
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    @push('scripts')
    <script>
    function pathEditor() {
        let keyCounter = {{ $path->levels->count() }};
        return {
            levels: @json($path->levels->map(fn ($l) => [
                '_key' => $l->id,
                'id' => $l->id,
                'title' => $l->title,
                'description' => $l->description ?? '',
                'module_count' => $l->modules->count(),
            ])->values()),
            addLevel() {
                keyCounter++;
                this.levels.push({ _key: 'new_' + keyCounter, id: null, title: '', description: '', module_count: 0 });
            },
            removeLevel(index) {
                if (this.levels[index].module_count > 0) return;
                this.levels.splice(index, 1);
            }
        };
    }
    </script>
    @endpush
</x-app-layout>
