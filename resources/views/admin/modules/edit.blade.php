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
            <h1 class="text-3xl font-bold text-brand-dark">Modul bearbeiten: {{ $module->title }}</h1>
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

        {{-- Quiz Editor --}}
        <x-card title="Quiz (Lernerfolgskontrolle)">
            <form method="POST" action="{{ route('admin.modules.quiz.store', $module) }}"
                  x-data="quizEditor(@js($module->quiz?->questions ?? []), {{ $module->quiz?->pass_percentage ?? 70 }})"
                  class="space-y-4">
                @csrf

                <div>
                    <label class="label">Bestehensgrenze (%)</label>
                    <input type="number" name="pass_percentage" class="input-field w-32" min="1" max="100" x-model="passPercentage">
                </div>

                <template x-for="(q, qi) in questions" :key="qi">
                    <div class="panel p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-brand-dark" x-text="'Frage ' + (qi + 1)"></span>
                            <button type="button" @click="removeQuestion(qi)" class="btn-danger btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <div>
                            <input type="text" :name="'questions[' + qi + '][question]'" class="input-field" placeholder="Frage..."
                                   x-model="q.question" required>
                        </div>

                        <template x-for="(opt, oi) in q.options" :key="oi">
                            <div class="flex items-center gap-2">
                                <input type="radio" :name="'questions[' + qi + '][correct]'" :value="oi" class="radio-field"
                                       x-model.number="q.correct">
                                <input type="text" :name="'questions[' + qi + '][options][' + oi + ']'" class="input-field flex-1"
                                       placeholder="Antwort..." x-model="q.options[oi]" required>
                                <button type="button" @click="q.options.splice(oi, 1); if (q.correct >= q.options.length) q.correct = 0"
                                        class="btn-ghost btn-xs" x-show="q.options.length > 2">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <button type="button" @click="q.options.push('')" class="btn-ghost btn-xs">
                            + Antwort hinzufügen
                        </button>
                    </div>
                </template>

                <button type="button" @click="addQuestion()" class="btn-secondary w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Frage hinzufügen
                </button>

                <div class="flex justify-end pt-4 border-t border-surface-200" x-show="questions.length > 0">
                    <button type="submit" class="btn-primary">Quiz speichern</button>
                </div>
            </form>
        </x-card>
    </div>

    @push('scripts')
    <script>
    function quizEditor(initialQuestions, initialPass) {
        return {
            questions: initialQuestions.length ? initialQuestions : [],
            passPercentage: initialPass,
            addQuestion() {
                this.questions.push({ question: '', options: ['', '', ''], correct: 0 });
            },
            removeQuestion(index) {
                this.questions.splice(index, 1);
            }
        };
    }
    </script>
    @endpush
</x-app-layout>
