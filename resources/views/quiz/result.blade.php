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
