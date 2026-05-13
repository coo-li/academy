<x-app-layout>
    @section('page-title', 'Quiz Ergebnis')

    <div class="max-w-2xl mx-auto space-y-6">
        {{-- Result Header --}}
        <div class="card-tool">
            <div class="card-tool-body text-center py-8">
                @if($passed)
                <div class="w-20 h-20 rounded-full bg-ui-success-light flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-ui-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-ui-success mb-2">Bestanden!</h1>
                <p class="text-surface-500">Herzlichen Glückwunsch! Du hast das Quiz erfolgreich abgeschlossen.</p>
                @else
                <div class="w-20 h-20 rounded-full bg-ui-error-light flex items-center justify-center mx-auto mb-4">
                    <svg class="w-10 h-10 text-ui-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold text-ui-error mb-2">Leider nicht bestanden</h1>
                <p class="text-surface-500">Du kannst das Quiz jederzeit erneut versuchen.</p>
                @endif

                <div class="mt-6 grid grid-cols-3 gap-4 max-w-sm mx-auto">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-brand-dark">{{ $score }}%</div>
                        <div class="text-xs text-surface-500">Ergebnis</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-ui-success">{{ $correct }}</div>
                        <div class="text-xs text-surface-500">Richtig</div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-ui-error">{{ $total - $correct }}</div>
                        <div class="text-xs text-surface-500">Falsch</div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="progress-bar max-w-xs mx-auto">
                        <div class="progress-bar-fill {{ $passed ? 'progress-bar-success' : 'progress-bar-error' }}" style="width: {{ $score }}%"></div>
                    </div>
                    <p class="text-xs text-surface-500 mt-1">Bestehensgrenze: {{ $quiz->pass_percentage }}%</p>
                </div>
            </div>
        </div>

        {{-- Alerts --}}
        @if($passed)
        <x-alert type="success" title="Modul abgeschlossen!">
            Das Modul „{{ $quiz->module->title }}" wurde automatisch als abgeschlossen markiert. Dein Fortschritt wurde aktualisiert.
        </x-alert>
        @else
        <x-alert type="info" title="Tipp">
            Wiederhole die Lernmaterialien und versuche das Quiz erneut. Du benötigst mindestens {{ $quiz->pass_percentage }}% um zu bestehen.
        </x-alert>
        @endif

        {{-- Per-Question Details --}}
        @if(!empty($details))
        <x-card title="Auswertung pro Frage">
            <div class="space-y-3">
                @foreach($quiz->questions as $idx => $question)
                @php
                    $detail = $details[$idx] ?? null;
                    $isCorrect = $detail['correct'] ?? false;
                    $type = $question['type'] ?? 'single_choice';
                @endphp
                <div class="p-3 rounded-lg border {{ $isCorrect ? 'border-ui-success bg-ui-success-light' : 'border-ui-error bg-ui-error-light' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-medium {{ $isCorrect ? 'text-ui-success' : 'text-ui-error' }}">
                                    Frage {{ $idx + 1 }}
                                </span>
                                <span class="badge-neutral text-xs">
                                    @switch($type)
                                        @case('single_choice') Single Choice @break
                                        @case('multiple_choice') Multiple Choice @break
                                        @case('true_false') Wahr / Falsch @break
                                        @case('short_answer') Kurzantwort @break
                                        @case('ordering') Reihenfolge @break
                                        @case('matching') Zuordnung @break
                                        @case('cloze') Lückentext @break
                                    @endswitch
                                </span>
                            </div>
                            <p class="text-sm font-medium text-brand-dark">{{ $question['question'] }}</p>

                            {{-- Show user's answer vs expected --}}
                            <div class="mt-2 text-xs text-surface-600 space-y-1">
                                @switch($type)
                                    @case('single_choice')
                                        @php
                                            $userIdx = $detail['user_answer'] ?? null;
                                            $correctIdx = $question['correct'];
                                        @endphp
                                        <p>Deine Antwort: <span class="font-medium">{{ $question['options'][$userIdx] ?? '–' }}</span></p>
                                        @if(!$isCorrect)
                                        <p>Richtig: <span class="font-medium text-ui-success">{{ $question['options'][$correctIdx] ?? '' }}</span></p>
                                        @endif
                                        @break

                                    @case('multiple_choice')
                                        @php
                                            $userIdxs = (array) ($detail['user_answer'] ?? []);
                                            $correctIdxs = $question['correct'];
                                        @endphp
                                        <p>Deine Auswahl: <span class="font-medium">{{ collect($userIdxs)->map(fn($i) => $question['options'][(int)$i] ?? '?')->implode(', ') ?: '–' }}</span></p>
                                        @if(!$isCorrect)
                                        <p>Richtig: <span class="font-medium text-ui-success">{{ collect($correctIdxs)->map(fn($i) => $question['options'][$i] ?? '?')->implode(', ') }}</span></p>
                                        @endif
                                        @break

                                    @case('true_false')
                                        @php
                                            $userVal = $detail['user_answer'] ?? null;
                                            $correctVal = $question['correct'];
                                        @endphp
                                        <p>Deine Antwort: <span class="font-medium">{{ $userVal === 'true' || $userVal === '1' ? 'Wahr' : 'Falsch' }}</span></p>
                                        @if(!$isCorrect)
                                        <p>Richtig: <span class="font-medium text-ui-success">{{ $correctVal ? 'Wahr' : 'Falsch' }}</span></p>
                                        @endif
                                        @break

                                    @case('short_answer')
                                        <p>Deine Antwort: <span class="font-medium">{{ $detail['user_answer'] ?? '–' }}</span></p>
                                        @if(!$isCorrect)
                                        <p>Akzeptiert: <span class="font-medium text-ui-success">{{ implode(', ', $question['accepted_answers'] ?? []) }}</span></p>
                                        @endif
                                        @break

                                    @case('ordering')
                                        @php
                                            $userOrder = (array) ($detail['user_answer'] ?? []);
                                            $correctOrder = $question['correct_order'] ?? [];
                                        @endphp
                                        <p>Deine Reihenfolge: <span class="font-medium">{{ collect($userOrder)->map(fn($i) => $question['items'][(int)$i] ?? '?')->implode(' → ') ?: '–' }}</span></p>
                                        @if(!$isCorrect)
                                        <p>Richtig: <span class="font-medium text-ui-success">{{ collect($correctOrder)->map(fn($i) => $question['items'][$i] ?? '?')->implode(' → ') }}</span></p>
                                        @endif
                                        @break

                                    @case('matching')
                                        @if(!$isCorrect)
                                        <p>Richtige Zuordnung:</p>
                                        @foreach($question['correct_pairs'] ?? [] as $leftIdx => $rightIdx)
                                        <p class="font-medium text-ui-success">{{ $question['left'][$leftIdx] ?? '?' }} → {{ $question['right'][$rightIdx] ?? '?' }}</p>
                                        @endforeach
                                        @endif
                                        @break

                                    @case('cloze')
                                        @php $userBlanks = (array) ($detail['user_answer'] ?? []); @endphp
                                        @foreach($question['blanks'] ?? [] as $bi => $blank)
                                        <p>Lücke {{ $bi + 1 }}: <span class="font-medium">{{ $userBlanks[$bi] ?? '–' }}</span>
                                            @if(!$isCorrect)
                                            (Akzeptiert: <span class="text-ui-success">{{ implode(', ', $blank['accepted_answers'] ?? []) }}</span>)
                                            @endif
                                        </p>
                                        @endforeach
                                        @break
                                @endswitch
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            @if($isCorrect)
                            <svg class="w-6 h-6 text-ui-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            @else
                            <svg class="w-6 h-6 text-ui-error" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </x-card>
        @endif

        {{-- Actions --}}
        <div class="flex items-center justify-center gap-4">
            <a href="{{ route('dashboard') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Zum Dashboard
            </a>
            @if(!$passed)
            <a href="{{ route('quiz.show', $quiz) }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Quiz wiederholen
            </a>
            @endif
        </div>
    </div>
</x-app-layout>
