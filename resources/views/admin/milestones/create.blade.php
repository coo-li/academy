<x-app-layout>
    @section('page-title', 'Milestone erstellen')

    <div class="max-w-2xl mx-auto space-y-6">
        <div>
            <a href="{{ route('admin.milestones.index') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Zurück zur Übersicht
            </a>
            <h1 class="text-3xl font-bold text-brand-dark">Neuer Milestone</h1>
        </div>

        <x-card title="Milestone-Details">
            <form method="POST" action="{{ route('admin.milestones.store') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="label label-required">Karrierestufe</label>
                    <select name="career_level_id" class="select-field" required>
                        <option value="">Stufe wählen&hellip;</option>
                        @foreach($levels as $pathName => $pathLevels)
                        <optgroup label="{{ $pathName }}">
                            @foreach($pathLevels as $level)
                            <option value="{{ $level->id }}" {{ old('career_level_id', request('career_level_id')) == $level->id ? 'selected' : '' }}>
                                Level {{ $level->level_number }}: {{ $level->title }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    @error('career_level_id') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Team</label>
                    <select name="team_id" class="select-field">
                        <option value="">Alle Teams (teamübergreifend)</option>
                        @foreach($teams as $team)
                        <option value="{{ $team->id }}" {{ old('team_id', request('team_id')) == $team->id ? 'selected' : '' }}>
                            {{ $team->name }}
                        </option>
                        @endforeach
                    </select>
                    <p class="help-text mt-1">Leer lassen, wenn der Milestone für alle Teams gilt.</p>
                    @error('team_id') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label label-required">Kategorie</label>
                        <select name="category" class="select-field" required>
                            @foreach(\App\Models\Milestone::CATEGORIES as $key => $label)
                            <option value="{{ $key }}" {{ old('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label label-required">Typ</label>
                        <select name="type" class="select-field" required>
                            @foreach(\App\Models\Milestone::TYPES as $key => $label)
                            <option value="{{ $key }}" {{ old('type', 'passiv') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="label label-required">Titel</label>
                    <input type="text" name="title" class="input-field" required value="{{ old('title') }}" placeholder="z.B. Budgettreue bei Projekten">
                    @error('title') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Beschreibung</label>
                    <textarea name="description" class="input-field" rows="3" placeholder="Optionale ausführliche Beschreibung...">{{ old('description') }}</textarea>
                    @error('description') <p class="error-text">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="label">Sortierung</label>
                    <input type="number" name="sort_order" class="input-field w-32" min="0" value="{{ old('sort_order', 0) }}">
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-surface-200">
                    <a href="{{ route('admin.milestones.index') }}" class="btn-secondary">Abbrechen</a>
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Milestone erstellen
                    </button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
