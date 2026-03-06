@php
    $statusConfig = match($enrollment->status) {
        'completed' => [
            'marker' => 'timeline-marker-success',
            'badge' => 'success',
            'label' => 'Erledigt',
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'enrolled' => [
            'marker' => 'timeline-marker-primary',
            'badge' => 'primary',
            'label' => 'Gebucht',
            'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        'attended' => [
            'marker' => 'timeline-marker-primary',
            'badge' => 'warning',
            'label' => 'Quiz offen',
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        ],
        'cancelled' => [
            'marker' => '',
            'badge' => 'error',
            'label' => 'Storniert',
            'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
        default => [
            'marker' => '',
            'badge' => 'neutral',
            'label' => $enrollment->status,
            'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        ],
    };
@endphp

<div class="timeline-item">
    <div class="timeline-marker {{ $statusConfig['marker'] }}"></div>
    <div class="timeline-content">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
            <div class="flex-1">
                <div class="flex items-center gap-2 flex-wrap">
                    <h3 class="timeline-title">{{ $enrollment->module->title }}</h3>
                    <x-badge :type="$statusConfig['badge']">{{ $statusConfig['label'] }}</x-badge>
                    @if($enrollment->module->is_mandatory)
                        <x-badge type="error">Pflicht</x-badge>
                    @endif
                </div>

                <p class="timeline-time">
                    Eingeschrieben am {{ $enrollment->created_at->format('d.m.Y, H:i') }} Uhr
                </p>

                @if($enrollment->trainingSession)
                <p class="timeline-description">
                    <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    Workshop: {{ $enrollment->trainingSession->start_at->format('d.m.Y, H:i') }} Uhr
                    @if($enrollment->trainingSession->location)
                        &middot; {{ $enrollment->trainingSession->location }}
                    @endif
                </p>
                @endif

                @if($enrollment->attendance_confirmed_at)
                <p class="timeline-description text-ui-warning">
                    <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Teilnahme bestätigt am {{ $enrollment->attendance_confirmed_at->format('d.m.Y, H:i') }} Uhr
                </p>
                @endif

                @if($enrollment->completed_at)
                <p class="timeline-description text-ui-success">
                    <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Abgeschlossen am {{ $enrollment->completed_at->format('d.m.Y, H:i') }} Uhr
                </p>
                @endif

                @if($enrollment->cancelled_at)
                <p class="timeline-description text-ui-error">
                    Storniert am {{ $enrollment->cancelled_at->format('d.m.Y, H:i') }} Uhr
                </p>
                @endif
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-2 flex-shrink-0" x-data="{ showConfirm: false }">
                @if($enrollment->isAttended() && $enrollment->module->quiz)
                    <a href="{{ route('quiz.show', $enrollment->module->quiz) }}" class="btn-primary btn-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Quiz starten
                    </a>
                @endif

                @if($enrollment->status === 'enrolled')
                    <button x-show="!showConfirm" @click="showConfirm = true" class="btn-danger btn-sm" title="Buchung stornieren">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Stornieren
                    </button>

                    <div x-show="showConfirm" x-cloak x-transition class="flex items-center gap-2">
                        <span class="text-sm text-ui-error">Wirklich stornieren?</span>
                        <form method="POST" action="{{ route('enrollment.cancel', $enrollment) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-danger btn-sm">Ja</button>
                        </form>
                        <button @click="showConfirm = false" class="btn-secondary btn-sm">Nein</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
