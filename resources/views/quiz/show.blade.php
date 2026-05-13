<x-app-layout>
    @section('page-title', 'Quiz: ' . $quiz->module->title)

    <div class="max-w-3xl mx-auto space-y-6">
        {{-- Header --}}
        <div>
            <a href="{{ route('dashboard') }}" class="text-sm text-brand-primary hover:text-brand-primary-hover mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Zurück zum Dashboard
            </a>
            <h1 class="text-3xl font-bold text-brand-dark">Lernerfolgskontrolle</h1>
            <p class="text-surface-500 mt-1">{{ $quiz->module->title }}</p>
        </div>

        {{-- Quiz Info --}}
        <div class="card-tool">
            <div class="card-tool-body">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm text-surface-600">{{ count($quiz->questions) }} Fragen</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm text-surface-600">Bestehen ab {{ $quiz->pass_percentage }}%</span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($quiz->module->method)
                        <span class="badge-info">{{ $quiz->module->method->name }}</span>
                        @endif
                        @if($quiz->module->careerLevel)
                        <span class="badge-neutral">{{ $quiz->module->careerLevel->title }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Previous Attempts --}}
        @if($previousAttempts->isNotEmpty())
        <x-card title="Bisherige Versuche">
            <div class="space-y-2">
                @foreach($previousAttempts as $attempt)
                <div class="flex items-center justify-between p-2 rounded {{ $attempt->passed ? 'bg-ui-success-light' : 'bg-surface-100' }}">
                    <div class="flex items-center gap-3">
                        @if($attempt->passed)
                        <span class="badge-success">Bestanden</span>
                        @else
                        <span class="badge-error">Nicht bestanden</span>
                        @endif
                        <span class="text-sm text-surface-600">{{ $attempt->score }}%</span>
                    </div>
                    <span class="text-xs text-surface-500">{{ $attempt->created_at->format('d.m.Y H:i') }}</span>
                </div>
                @endforeach
            </div>
        </x-card>
        @endif

        {{-- Quiz Form --}}
        <form method="POST" action="{{ route('quiz.submit', $quiz) }}"
              x-data="quizPlayer({{ Js::from($quiz->questions) }})">
            @csrf

            {{-- Question Navigation Dots --}}
            <div class="flex items-center justify-center gap-2 mb-4">
                @foreach($quiz->questions as $qIdx => $q)
                <button type="button"
                        @click="goTo({{ $qIdx }})"
                        class="w-8 h-8 rounded-full text-xs font-medium transition-colors border"
                        :class="{
                            'bg-brand-primary text-white border-brand-primary': currentQuestion === {{ $qIdx }},
                            'bg-ui-success-light text-ui-success border-ui-success': currentQuestion !== {{ $qIdx }} && isAnswered({{ $qIdx }}),
                            'bg-surface-100 text-surface-500 border-surface-200': currentQuestion !== {{ $qIdx }} && !isAnswered({{ $qIdx }})
                        }">
                    {{ $qIdx + 1 }}
                </button>
                @endforeach
            </div>

            <div class="space-y-4">
                @foreach($quiz->questions as $index => $question)
                @php $type = $question['type'] ?? 'single_choice'; @endphp
                <div class="card-tool" x-show="currentQuestion === {{ $index }}" x-transition>
                    <div class="card-tool-header">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-surface-500">Frage {{ $index + 1 }} von {{ count($quiz->questions) }}</span>
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
                        <div class="progress-bar w-32">
                            <div class="progress-bar-fill" :style="'width: ' + progress + '%'"></div>
                        </div>
                    </div>
                    <div class="card-tool-body">
                        <h3 class="text-lg font-semibold text-brand-dark mb-4">{{ $question['question'] }}</h3>

                        @switch($type)
                            @case('single_choice')
                                <x-quiz.single-choice :question="$question" :index="$index" />
                                @break
                            @case('multiple_choice')
                                <x-quiz.multiple-choice :question="$question" :index="$index" />
                                @break
                            @case('true_false')
                                <x-quiz.true-false :question="$question" :index="$index" />
                                @break
                            @case('short_answer')
                                <x-quiz.short-answer :question="$question" :index="$index" />
                                @break
                            @case('ordering')
                                <x-quiz.ordering :question="$question" :index="$index" />
                                @break
                            @case('matching')
                                <x-quiz.matching :question="$question" :index="$index" />
                                @break
                            @case('cloze')
                                <x-quiz.cloze :question="$question" :index="$index" />
                                @break
                        @endswitch
                    </div>
                    <div class="card-tool-footer">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="prev()"
                                    class="btn-secondary btn-sm" x-show="currentQuestion > 0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Zurück
                            </button>
                            <div x-show="currentQuestion === 0"></div>

                            @if($index < count($quiz->questions) - 1)
                            <button type="button" @click="next()"
                                    class="btn-primary btn-sm" :disabled="!canProceed">
                                Weiter
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            @else
                            <button type="submit" class="btn-success"
                                    :disabled="!allAnswered">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Quiz abschließen
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </form>
    </div>
</x-app-layout>
