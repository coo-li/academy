<x-app-layout>
    @section('page-title', 'Karrierepfad erstellen')

    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('admin.modules.index') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Zurück zur Übersicht
            </a>
            <h1 class="text-3xl font-bold text-brand-dark">Neuer Karrierepfad</h1>
            <p class="text-surface-500 mt-1">Erstelle einen Pfad mit Karrierestufen (z.B. Junior SEO → Senior SEO).</p>
        </div>

        <x-card title="Karrierepfad">
            <form method="POST" action="{{ route('admin.paths.store') }}"
                  x-data="pathEditor()" class="space-y-4">
                @csrf

                <div>
                    <label class="label label-required">Name des Pfades</label>
                    <input type="text" name="name" class="input-field" required placeholder="z.B. SEO Specialist" value="{{ old('name') }}">
                    @error('name') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Beschreibung</label>
                    <textarea name="description" class="input-field" rows="2" placeholder="Kurze Beschreibung des Karrierepfades...">{{ old('description') }}</textarea>
                </div>

                <div>
                    <label class="label label-required">Karrierestufen</label>
                    <p class="help-text mb-3">Definiere die Stufen von unten (Einstieg) nach oben (Experte).</p>

                    <div class="space-y-3">
                        <template x-for="(level, index) in levels" :key="index">
                            <div class="panel-compact flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-brand-primary-light flex items-center justify-center text-sm font-bold text-brand-primary flex-shrink-0 mt-1" x-text="index + 1"></div>
                                <div class="flex-1 space-y-2">
                                    <input type="text" :name="'levels[' + index + '][title]'" class="input-field" required
                                           placeholder="z.B. Junior SEO" x-model="level.title">
                                    <input type="text" :name="'levels[' + index + '][description]'" class="input-field"
                                           placeholder="Optionale Beschreibung..." x-model="level.description">
                                </div>
                                <button type="button" @click="levels.splice(index, 1)" class="btn-danger btn-xs mt-1" x-show="levels.length > 1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="levels.push({ title: '', description: '' })" class="btn-ghost btn-sm mt-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Stufe hinzufügen
                    </button>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-200">
                    <a href="{{ route('admin.modules.index') }}" class="btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Pfad erstellen
                    </button>
                </div>
            </form>
        </x-card>
    </div>

    @push('scripts')
    <script>
    function pathEditor() {
        return {
            levels: [
                { title: '', description: '' },
                { title: '', description: '' },
                { title: '', description: '' }
            ]
        };
    }
    </script>
    @endpush
</x-app-layout>
