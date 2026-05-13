<x-app-layout>
    @section('page-title', $module->title)

    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('trainer.schulungen.index') }}" class="text-sm text-surface-500 hover:text-brand-primary mb-1 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Zurück zur Übersicht
                </a>
                <h1 class="text-3xl font-bold text-brand-dark">{{ $module->title }}</h1>
                <div class="flex flex-wrap gap-2 mt-2">
                    @if($module->skillCategory)
                        <span class="badge-neutral">{{ $module->skillCategory->name }}</span>
                    @endif
                    @if($module->method)
                        <span class="badge-info">{{ $module->method->name }}</span>
                    @endif
                    @if($module->is_mandatory)
                        <span class="badge-error">Pflicht</span>
                    @endif
                </div>
            </div>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('admin.modules.edit', $module) }}" class="btn-secondary btn-sm self-start">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Modul bearbeiten
            </a>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Description --}}
            <x-card title="Beschreibung bearbeiten">
                <form method="POST" action="{{ route('trainer.schulungen.update', $module) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="label">Schulungsbeschreibung</label>
                        <textarea name="description" rows="6" class="input-field w-full" placeholder="Beschreibung der Schulung...">{{ old('description', $module->description) }}</textarea>
                        @error('description') <p class="error-text">{{ $message }}</p> @enderror
                    </div>

                    @if(($module->method?->scheduling_type ?? 'scheduled') !== 'self_study')
                    <div>
                        <label class="label">Standard-Kalenderbeschreibung</label>
                        <textarea name="calendar_description" rows="3" class="input-field w-full" placeholder="Wird automatisch in neue Kalendertermine übernommen...">{{ old('calendar_description', $module->calendar_description) }}</textarea>
                        <p class="text-xs text-surface-400 mt-1">Wird beim Anlegen neuer Termine als Kalenderbeschreibung vorgeschlagen.</p>
                        @error('calendar_description') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    @endif

                    <button type="submit" class="btn-primary">
                        Speichern
                    </button>
                </form>
            </x-card>

            {{-- Materials Upload + Links --}}
            <x-card title="Schulungsunterlagen">
                <div x-data="{ tab: 'file' }" class="mb-4">
                    <div class="flex gap-1 mb-4 border-b border-surface-200">
                        <button type="button"
                                @click="tab = 'file'"
                                :class="tab === 'file' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-surface-500 hover:text-brand-dark'"
                                class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                            Datei hochladen
                        </button>
                        <button type="button"
                                @click="tab = 'link'"
                                :class="tab === 'link' ? 'border-brand-primary text-brand-primary' : 'border-transparent text-surface-500 hover:text-brand-dark'"
                                class="px-3 py-2 text-sm font-medium border-b-2 -mb-px transition-colors">
                            Link hinzufügen
                        </button>
                    </div>

                    {{-- File Upload Tab --}}
                    <form method="POST" action="{{ route('trainer.schulungen.materials.store', $module) }}" enctype="multipart/form-data" x-show="tab === 'file'" x-cloak>
                        @csrf
                        <div class="flex items-end gap-3">
                            <div class="flex-1">
                                <label class="label label-required">Datei hochladen</label>
                                <input type="file" name="file" class="input-field" required
                                       accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.ppt,.pptx,.xls,.xlsx">
                                <p class="text-xs text-surface-400 mt-1">Max. 20 MB. PDF, Bilder, Office-Dokumente.</p>
                            </div>
                            <button type="submit" class="btn-primary btn-sm flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                                </svg>
                                Hochladen
                            </button>
                        </div>
                        @error('file') <p class="error-text mt-1">{{ $message }}</p> @enderror
                    </form>

                    {{-- Link Tab --}}
                    <form method="POST" action="{{ route('trainer.schulungen.links.store', $module) }}" x-show="tab === 'link'" x-cloak>
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="label label-required">URL</label>
                                <input type="url" name="url" class="input-field" required
                                       placeholder="https://..." value="{{ old('url') }}">
                                @error('url') <p class="error-text mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div class="flex items-end gap-3">
                                <div class="flex-1">
                                    <label class="label">Anzeigename</label>
                                    <input type="text" name="link_title" class="input-field"
                                           placeholder="z.B. Notion-Seite, Google Slides..." value="{{ old('link_title') }}">
                                </div>
                                <button type="submit" class="btn-primary btn-sm flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                    Hinzufügen
                                </button>
                            </div>
                            @error('link_title') <p class="error-text mt-1">{{ $message }}</p> @enderror
                        </div>
                    </form>
                </div>

                @if($module->trainingMaterials->isNotEmpty())
                <div class="divide-y divide-surface-200">
                    @foreach($module->trainingMaterials as $material)
                    <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex-shrink-0">
                                @if($material->isLink())
                                    <svg class="w-8 h-8 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                    </svg>
                                @elseif(str_starts_with($material->mime_type, 'image/'))
                                    <svg class="w-8 h-8 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                @else
                                    <svg class="w-8 h-8 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                @if($material->isLink())
                                    <a href="{{ $material->url }}" target="_blank" rel="noopener noreferrer"
                                       class="text-sm font-medium text-brand-primary hover:text-brand-primary-hover hover:underline truncate block">
                                        {{ $material->displayName() }}
                                    </a>
                                @else
                                    <div class="text-sm font-medium text-brand-dark truncate">{{ $material->displayName() }}</div>
                                @endif
                                <div class="text-xs text-surface-500">
                                    @if($material->isFile())
                                        {{ number_format($material->file_size / 1024, 0) }} KB &middot;
                                    @endif
                                    {{ $material->created_at->format('d.m.Y, H:i') }}
                                    @if($material->uploader)
                                        &middot; {{ $material->uploader->name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('trainer.schulungen.materials.destroy', $material) }}"
                              onsubmit="return confirm('Unterlage wirklich löschen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-sm text-surface-500 text-center py-4">
                    Noch keine Unterlagen vorhanden.
                </div>
                @endif
            </x-card>
        </div>

        {{-- Training Sessions --}}
        <x-card title="Termine für dieses Modul">
            @if($module->trainingSessions->isNotEmpty())
            <div class="divide-y divide-surface-200">
                @foreach($module->trainingSessions->sortByDesc('start_at') as $session)
                <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                    <div>
                        <div class="font-medium text-brand-dark">{{ $session->start_at->format('d.m.Y') }}</div>
                        <div class="text-xs text-surface-500">{{ $session->start_at->format('H:i') }} – {{ $session->end_at->format('H:i') }} Uhr</div>
                    </div>
                    @if($session->location)
                        <span class="text-xs text-surface-500">{{ $session->location }}</span>
                    @endif
                    <a href="{{ route('trainer.teilnehmer.index') }}" class="badge-info hover:bg-brand-primary hover:text-white transition-colors" title="Teilnehmermanagement öffnen">
                        <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        {{ $session->enrollments->count() }} Teilnehmer
                    </a>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-sm text-surface-500 text-center py-4">
                Noch keine Termine für dieses Modul angelegt.
            </div>
            @endif
        </x-card>

        {{-- Quiz Editor --}}
        <x-card title="Quiz (Lernerfolgskontrolle)">
            <form method="POST" action="{{ route('trainer.schulungen.quiz.store', $module) }}"
                  x-data="quizEditor({{ Js::from($module->quiz?->questions ?? []) }}, {{ $module->quiz?->pass_percentage ?? 70 }})"
                  class="space-y-4">
                @csrf

                <div>
                    <label class="label">Bestehensgrenze (%)</label>
                    <input type="number" name="pass_percentage" class="input-field w-32" min="1" max="100" x-model="passPercentage">
                </div>

                <template x-for="(q, qi) in questions" :key="qi">
                    <div class="panel p-4 space-y-3">
                        {{-- Question Header --}}
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="text-sm font-medium text-brand-dark" x-text="'Frage ' + (qi + 1)"></span>
                                <input type="hidden" :name="'questions[' + qi + '][type]'" x-model="q.type">
                                <select class="input-field text-xs py-1 px-2 w-auto"
                                        x-model="q.type"
                                        @change="changeType(qi, $event.target.value)">
                                    <template x-for="t in questionTypes" :key="t.value">
                                        <option :value="t.value" x-text="t.label" :selected="t.value === q.type"></option>
                                    </template>
                                </select>
                            </div>
                            <button type="button" @click="removeQuestion(qi)" class="btn-danger btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        {{-- Question Text --}}
                        <div>
                            <input type="text" :name="'questions[' + qi + '][question]'" class="input-field" placeholder="Frage..."
                                   x-model="q.question" required>
                        </div>

                        {{-- === SINGLE CHOICE === --}}
                        <template x-if="q.type === 'single_choice'">
                            <div class="space-y-2">
                                <label class="label text-xs">Antworten (Radio = richtige Antwort)</label>
                                <template x-for="(opt, oi) in q.options" :key="oi">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" :name="'questions[' + qi + '][correct]'" :value="oi" class="radio-field"
                                               x-model.number="q.correct">
                                        <input type="text" :name="'questions[' + qi + '][options][' + oi + ']'" class="input-field flex-1"
                                               placeholder="Antwort..." x-model="q.options[oi]" required>
                                        <button type="button" @click="removeOption(qi, oi)"
                                                class="btn-ghost btn-xs" x-show="q.options.length > 2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addOption(qi)" class="btn-ghost btn-xs">+ Antwort</button>
                            </div>
                        </template>

                        {{-- === MULTIPLE CHOICE === --}}
                        <template x-if="q.type === 'multiple_choice'">
                            <div class="space-y-2">
                                <label class="label text-xs">Antworten (Checkbox = richtige Antworten, Alles-oder-nichts)</label>
                                <template x-for="(opt, oi) in q.options" :key="oi">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" class="checkbox-field"
                                               :checked="isCorrectMultiple(qi, oi)"
                                               @change="toggleCorrectMultiple(qi, oi)">
                                        <input type="text" :name="'questions[' + qi + '][options][' + oi + ']'" class="input-field flex-1"
                                               placeholder="Antwort..." x-model="q.options[oi]" required>
                                        <button type="button" @click="removeOption(qi, oi)"
                                                class="btn-ghost btn-xs" x-show="q.options.length > 2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <template x-for="c in q.correct" :key="'mc-correct-' + c">
                                    <input type="hidden" :name="'questions[' + qi + '][correct][]'" :value="c">
                                </template>
                                <button type="button" @click="addOption(qi)" class="btn-ghost btn-xs">+ Antwort</button>
                            </div>
                        </template>

                        {{-- === TRUE / FALSE === --}}
                        <template x-if="q.type === 'true_false'">
                            <div class="space-y-2">
                                <label class="label text-xs">Richtige Antwort</label>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" :name="'questions[' + qi + '][correct]'" value="1" class="radio-field"
                                               :checked="q.correct === true || q.correct === 1 || q.correct === '1'"
                                               @change="q.correct = true">
                                        <span class="text-sm">Wahr</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="radio" :name="'questions[' + qi + '][correct]'" value="0" class="radio-field"
                                               :checked="q.correct === false || q.correct === 0 || q.correct === '0'"
                                               @change="q.correct = false">
                                        <span class="text-sm">Falsch</span>
                                    </label>
                                </div>
                            </div>
                        </template>

                        {{-- === SHORT ANSWER === --}}
                        <template x-if="q.type === 'short_answer'">
                            <div class="space-y-2">
                                <label class="label text-xs">Akzeptierte Antworten (case-insensitive)</label>
                                <template x-for="(ans, ai) in q.accepted_answers" :key="ai">
                                    <div class="flex items-center gap-2">
                                        <input type="text" :name="'questions[' + qi + '][accepted_answers][' + ai + ']'" class="input-field flex-1"
                                               placeholder="Gültige Antwort..." x-model="q.accepted_answers[ai]" required>
                                        <button type="button" @click="removeAcceptedAnswer(qi, ai)"
                                                class="btn-ghost btn-xs" x-show="q.accepted_answers.length > 1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addAcceptedAnswer(qi)" class="btn-ghost btn-xs">+ Alternative Antwort</button>
                            </div>
                        </template>

                        {{-- === ORDERING === --}}
                        <template x-if="q.type === 'ordering'">
                            <div class="space-y-2">
                                <label class="label text-xs">Elemente (in der richtigen Reihenfolge eingeben)</label>
                                <template x-for="(item, ii) in q.items" :key="ii">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-medium text-surface-500 w-6" x-text="(ii + 1) + '.'"></span>
                                        <input type="text" :name="'questions[' + qi + '][items][' + ii + ']'" class="input-field flex-1"
                                               placeholder="Element..." x-model="q.items[ii]" required>
                                        <input type="hidden" :name="'questions[' + qi + '][correct_order][' + ii + ']'" :value="ii">
                                        <button type="button" @click="removeOrderingItem(qi, ii)"
                                                class="btn-ghost btn-xs" x-show="q.items.length > 2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addOrderingItem(qi)" class="btn-ghost btn-xs">+ Element</button>
                                <p class="text-xs text-surface-400">Die Reihenfolge hier ist die richtige Reihenfolge. Dem Teilnehmer werden die Elemente zufällig angezeigt.</p>
                            </div>
                        </template>

                        {{-- === MATCHING === --}}
                        <template x-if="q.type === 'matching'">
                            <div class="space-y-2">
                                <label class="label text-xs">Paare (links gehört zu rechts in gleicher Zeile)</label>
                                <div class="grid grid-cols-2 gap-x-4 gap-y-2">
                                    <span class="text-xs font-medium text-surface-500">Links</span>
                                    <span class="text-xs font-medium text-surface-500">Rechts (Zuordnung)</span>
                                </div>
                                <template x-for="(_, pi) in q.left" :key="pi">
                                    <div class="flex items-center gap-2">
                                        <input type="text" :name="'questions[' + qi + '][left][' + pi + ']'" class="input-field flex-1"
                                               placeholder="Begriff..." x-model="q.left[pi]" required>
                                        <svg class="w-4 h-4 text-surface-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                        </svg>
                                        <input type="text" :name="'questions[' + qi + '][right][' + pi + ']'" class="input-field flex-1"
                                               placeholder="Zuordnung..." x-model="q.right[pi]" required>
                                        <input type="hidden" :name="'questions[' + qi + '][correct_pairs][' + pi + ']'" :value="pi">
                                        <button type="button" @click="removeMatchingPair(qi, pi)"
                                                class="btn-ghost btn-xs" x-show="q.left.length > 2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <button type="button" @click="addMatchingPair(qi)" class="btn-ghost btn-xs">+ Paar</button>
                                <p class="text-xs text-surface-400">Dem Teilnehmer wird die rechte Spalte gemischt angezeigt.</p>
                            </div>
                        </template>

                        {{-- === CLOZE === --}}
                        <template x-if="q.type === 'cloze'">
                            <div class="space-y-3">
                                <div>
                                    <label class="label text-xs">Textvorlage (Lücken mit @{{0}}, @{{1}}, ... markieren)</label>
                                    <textarea :name="'questions[' + qi + '][text_template]'" class="input-field w-full" rows="3"
                                              placeholder="PHP steht für @{{0}} Hypertext @{{1}}."
                                              x-model="q.text_template"></textarea>
                                </div>
                                <label class="label text-xs">Lücken-Definitionen</label>
                                <template x-for="(blank, bi) in q.blanks" :key="bi">
                                    <div class="panel p-3 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-xs font-medium text-surface-500" x-text="'Lücke {{' + bi + '}}'"></span>
                                            <button type="button" @click="removeClozeBlank(qi, bi)"
                                                    class="btn-ghost btn-xs" x-show="q.blanks.length > 1">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                        <template x-for="(ans, ai) in blank.accepted_answers" :key="ai">
                                            <div class="flex items-center gap-2">
                                                <input type="text" :name="'questions[' + qi + '][blanks][' + bi + '][accepted_answers][' + ai + ']'"
                                                       class="input-field flex-1" placeholder="Akzeptierte Antwort..."
                                                       x-model="blank.accepted_answers[ai]" required>
                                                <button type="button" @click="removeClozeAccepted(qi, bi, ai)"
                                                        class="btn-ghost btn-xs" x-show="blank.accepted_answers.length > 1">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        <button type="button" @click="addClozeAccepted(qi, bi)" class="btn-ghost btn-xs">+ Alternative</button>
                                    </div>
                                </template>
                                <button type="button" @click="addClozeBlank(qi)" class="btn-ghost btn-xs">+ Lücke</button>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Add Question Dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" class="btn-secondary w-full">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Frage hinzufügen
                    </button>
                    <div x-show="open" @click.outside="open = false" x-transition
                         class="absolute left-0 right-0 mt-1 bg-white border border-surface-200 rounded-lg shadow-lg z-10 py-1">
                        <template x-for="t in questionTypes" :key="t.value">
                            <button type="button"
                                    @click="addQuestion(t.value); open = false"
                                    class="w-full text-left px-4 py-2 text-sm text-brand-dark hover:bg-surface-50 transition-colors"
                                    x-text="t.label"></button>
                        </template>
                    </div>
                </div>

                <div class="flex justify-end pt-4 border-t border-surface-200" x-show="questions.length > 0">
                    <button type="submit" class="btn-primary">Quiz speichern</button>
                </div>
            </form>
        </x-card>
    </div>
</x-app-layout>
