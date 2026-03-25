<x-app-layout>
    @section('page-title', 'Modul bearbeiten')

    <div class="max-w-2xl mx-auto space-y-6">
        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif

        <div>
            <a href="{{ route('admin.modules.index') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Zurück zur Übersicht
            </a>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <h1 class="text-3xl font-bold text-brand-dark">Modul bearbeiten: {{ $module->title }}</h1>
                <a href="{{ route('trainer.schulungen.show', $module) }}" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    Schulungsinhalte verwalten
                </a>
            </div>
        </div>

        {{-- Module Form --}}
        <x-card title="Modul-Details">
            <form method="POST" action="{{ route('admin.modules.update', $module) }}" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="label">Karrierestufe</label>
                    <select name="career_level_id" class="select-field">
                        <option value="" {{ !$module->career_level_id ? 'selected' : '' }}>Allgemein (ohne Karrierestufe)</option>
                        @foreach($levels as $pathName => $pathLevels)
                        <optgroup label="{{ $pathName }}">
                            @foreach($pathLevels as $level)
                            <option value="{{ $level->id }}" {{ $module->career_level_id == $level->id ? 'selected' : '' }}>
                                Level {{ $level->level_number }}: {{ $level->title }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label label-required">Titel</label>
                    <input type="text" name="title" class="input-field" required value="{{ old('title', $module->title) }}">
                </div>

                <div>
                    <label class="label">Beschreibung</label>
                    <textarea name="description" class="input-field" rows="3">{{ old('description', $module->description) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Methode</label>
                        <select name="method_id" class="select-field">
                            <option value="">Keine Methode</option>
                            @foreach($methods as $method)
                            <option value="{{ $method->id }}" {{ old('method_id', $module->method_id) == $method->id ? 'selected' : '' }}>{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Skill-Kategorie</label>
                        <select name="skill_category_id" class="select-field">
                            <option value="">Keine Kategorie</option>
                            @foreach($skillCategories as $cat)
                            <option value="{{ $cat->id }}" {{ $module->skill_category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <div x-data="{ accountableType: '{{ old('accountable_type', $module->accountable_type ?? '') }}' }">
                        <label class="label">Verantwortliche/r (Accountable)</label>
                        <select name="accountable_type" class="select-field" x-model="accountableType">
                            <option value="">Nicht zugewiesen</option>
                            <option value="head_of" {{ old('accountable_type', $module->accountable_type) === 'head_of' ? 'selected' : '' }}>Eigener Head of</option>
                            <option value="user" {{ old('accountable_type', $module->accountable_type) === 'user' ? 'selected' : '' }}>Bestimmte Person...</option>
                        </select>

                        <div x-show="accountableType === 'user'" x-cloak class="mt-2">
                            <select name="accountable_user_id" class="select-field">
                                <option value="">Person wählen...</option>
                                @foreach($teachers as $teacher)
                                <option value="{{ $teacher->id }}" {{ old('accountable_user_id', $module->accountable_user_id) == $teacher->id ? 'selected' : '' }}>
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

                @php $selectedTrainerIds = old('trainer_ids', $module->trainers->pluck('id')->toArray()); @endphp
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
                    <input type="number" name="sort_order" class="input-field w-32" min="0" value="{{ old('sort_order', $module->sort_order) }}">
                </div>

                <label class="flex items-center gap-2">
                    <input type="hidden" name="is_mandatory" value="0">
                    <input type="checkbox" name="is_mandatory" value="1" class="checkbox-field" {{ $module->is_mandatory ? 'checked' : '' }}>
                    <span class="text-sm text-brand-dark">Pflichtmodul</span>
                </label>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-200">
                    <a href="{{ route('admin.modules.index') }}" class="btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn-primary">Speichern</button>
                </div>
            </form>
        </x-card>

    </div>
</x-app-layout>
