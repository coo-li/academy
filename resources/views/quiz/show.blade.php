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
        <form method="POST" action="{{ route('quiz.submit', $quiz) }}" x-data="{ currentQuestion: 0, answers: {} }">
            @csrf

            <div class="space-y-4">
                @foreach($quiz->questions as $index => $question)
                <div class="card-tool" x-show="currentQuestion === {{ $index }}" x-transition>
                    <div class="card-tool-header">
                        <span class="text-sm text-surface-500">Frage {{ $index + 1 }} von {{ count($quiz->questions) }}</span>
                        <div class="progress-bar w-32">
                            <div class="progress-bar-fill" style="width: {{ (($index + 1) / count($quiz->questions)) * 100 }}%"></div>
                        </div>
                    </div>
                    <div class="card-tool-body">
                        <h3 class="text-lg font-semibold text-brand-dark mb-4">{{ $question['question'] }}</h3>

                        <div class="space-y-2">
                            @foreach($question['options'] as $optIndex => $option)
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-surface-200 hover:border-brand-primary hover:bg-brand-primary-light cursor-pointer transition-colors"
                                   :class="{ 'border-brand-primary bg-brand-primary-light': answers[{{ $index }}] == {{ $optIndex }} }">
                                <input type="radio" name="answers[{{ $index }}]" value="{{ $optIndex }}" class="radio-field"
                                       x-model="answers[{{ $index }}]">
                                <span class="text-sm text-brand-dark">{{ $option }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="card-tool-footer">
                        <div class="flex items-center justify-between">
                            <button type="button" @click="currentQuestion--"
                                    class="btn-secondary btn-sm" x-show="currentQuestion > 0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Zurück
                            </button>
                            <div x-show="currentQuestion === 0"></div>

                            @if($index < count($quiz->questions) - 1)
                            <button type="button" @click="currentQuestion++"
                                    class="btn-primary btn-sm" :disabled="answers[{{ $index }}] === undefined">
                                Weiter
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            @else
                            <button type="submit" class="btn-success"
                                    :disabled="Object.keys(answers).length < {{ count($quiz->questions) }}">
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
