<x-app-layout>
    @section('page-title', 'Modul erstellen')

    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('admin.modules.index') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Zurück zur Übersicht
            </a>
            <h1 class="text-3xl font-bold text-brand-dark">Neues Modul erstellen</h1>
        </div>

        <x-card title="Modul-Details">
            <form method="POST" action="{{ route('admin.modules.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="label">Karrierestufe</label>
                    <select name="career_level_id" class="select-field">
                        <option value="">Allgemein (ohne Karrierestufe)</option>
                        @foreach($levels as $pathName => $pathLevels)
                        <optgroup label="{{ $pathName }}">
                            @foreach($pathLevels as $level)
                            <option value="{{ $level->id }}" {{ old('career_level_id') == $level->id ? 'selected' : '' }}>
                                Level {{ $level->level_number }}: {{ $level->title }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    @error('career_level_id') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label label-required">Titel</label>
                    <input type="text" name="title" class="input-field" required value="{{ old('title') }}" placeholder="z.B. Google Analytics Grundlagen">
                    @error('title') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Beschreibung</label>
                    <textarea name="description" class="input-field" rows="3" placeholder="Kurze Beschreibung des Moduls...">{{ old('description') }}</textarea>
                    @error('description') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Methode</label>
                        <select name="method_id" class="select-field">
                            <option value="">Keine Methode</option>
                            @foreach($methods as $method)
                            <option value="{{ $method->id }}" {{ old('method_id') == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
                        @error('method_id') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Skill-Kategorie</label>
                        <select name="skill_category_id" class="select-field">
                            <option value="">Keine Kategorie</option>
                            @foreach($skillCategories as $cat)
                            <option value="{{ $cat->id }}" {{ old('skill_category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('skill_category_id') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <div x-data="{ accountableType: '{{ old('accountable_type', '') }}' }">
                        <label class="label">Verantwortliche/r (Accountable)</label>
                        <select name="accountable_type" class="select-field" x-model="accountableType">
                            <option value="">Nicht zugewiesen</option>
                            <option value="head_of">Eigener Head of</option>
                            <option value="user">Bestimmte Person...</option>
                        </select>
                        @error('accountable_type') <p class="error-text">{{ $message }}</p> @enderror

                        <div x-show="accountableType === 'user'" x-cloak class="mt-2">
                            <select name="accountable_user_id" class="select-field">
                                <option value="">Person wählen...</option>
                                @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('accountable_user_id') == $teacher->id ? 'selected' : '' }}>
                                    {{ $teacher->name }}
                                </option>
                                @endforeach
                            </select>
                            @error('accountable_user_id') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <p x-show="accountableType === 'head_of'" x-cloak class="help-text mt-1">
                            Jedem Teilnehmer wird automatisch sein eigener Head of aus Personio zugewiesen.
                        </p>
                    </div>
                </div>

                @php $selectedTrainerIds = old('trainer_ids', []); @endphp
                <div>
                    <label class="label">Trainerpool</label>
                    <p class="text-xs text-surface-400 mb-2">Diese Trainer können Termine für dieses Modul erstellen.</p>
                    <div class="border border-surface-200 rounded-lg p-3 max-h-48 overflow-y-auto space-y-1">
                        @foreach($teachers as $teacher)
                        <label class="flex items-center gap-2 py-1 px-2 rounded hover:bg-surface-50 cursor-pointer">
                            <input type="checkbox" name="trainer_ids[]" value="{{ $teacher->id }}"
                                class="rounded border-surface-300 text-brand-primary focus:ring-brand-primary"
                                {{ in_array($teacher->id, $selectedTrainerIds) ? 'checked' : '' }}>
                            <span class="text-sm text-brand-dark">{{ $teacher->name }}</span>
                        </label>
                        @endforeach
                    </div>
                    @error('trainer_ids') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Sortierung</label>
                    <input type="number" name="sort_order" class="input-field w-32" min="0" value="{{ old('sort_order', 0) }}">
                </div>

                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_mandatory" value="0">
                    <input type="checkbox" name="is_mandatory" value="1" class="checkbox-field" {{ old('is_mandatory') ? 'checked' : '' }}>
                    <span class="text-sm text-brand-dark">Pflichtmodul (muss für Level-Aufstieg abgeschlossen werden)</span>
                </label>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-200">
                    <a href="{{ route('admin.modules.index') }}" class="btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Modul erstellen
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
