<x-app-layout>
    @section('page-title', 'Meine Academy')

    <div class="space-y-6">
        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Admin: Personio Sync Status --}}
        @if($user->isAdmin() && $personioStats)
            @if($personioStats['users_without_path'] > 0)
            <x-alert type="warning" title="Personio: {{ $personioStats['users_without_path'] }} Mitarbeiter ohne Karrierepfad" :dismissible="true">
                <a href="{{ route('admin.matrix.index') }}" class="underline font-medium">Karriere-Matrix öffnen</a>, um Positionen zuzuordnen.
            </x-alert>
            @endif

            @if($personioStats['last_sync'])
                @php $lastSync = $personioStats['last_sync']; @endphp
                @if(!$lastSync->isSuccess())
                <x-alert type="error" title="Letzter Personio-Sync fehlgeschlagen" :dismissible="true">
                    {{ $lastSync->started_at->format('d.m.Y, H:i') }} – {{ Str::limit($lastSync->error_message, 120) }}
                    <a href="{{ route('admin.matrix.index') }}" class="underline font-medium ml-1">Details anzeigen</a>
                </x-alert>
                @endif
            @endif
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Meine Academy</h1>
                <p class="text-surface-500 mt-1">Willkommen zurück, {{ $user->name }}!</p>
            </div>
            @if($careerLevel)
            <div class="flex items-center gap-3">
                <span class="badge-primary">{{ $careerPath?->name }}</span>
                <span class="text-lg font-semibold text-brand-dark">{{ $careerLevel->title }}</span>
            </div>
            @endif
        </div>

        {{-- Career Level Overview --}}
        @if($careerLevel)
        <div class="card-tool">
            <div class="card-tool-body">
                <div class="flex flex-col lg:flex-row lg:items-center gap-6">
                    {{-- Level Info --}}
                    <div class="flex items-center gap-4 flex-shrink-0">
                        <div class="w-16 h-16 rounded-full bg-brand-primary-light flex items-center justify-center">
                            <span class="text-2xl font-bold text-brand-primary">{{ $careerLevel->level_number }}</span>
                        </div>
                        <div>
                            <div class="text-xs text-surface-500 uppercase tracking-wide">Deine Ziel-Karrierestufe</div>
                            <div class="text-xl font-bold text-brand-dark">{{ $careerLevel->title }}</div>
                            <div class="text-sm text-surface-500">{{ $careerPath?->name }}</div>
                        </div>
                    </div>

                    {{-- Progress Stats --}}
                    <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-brand-dark">{{ $stats['total'] }}</div>
                            <div class="text-xs text-surface-500">Module gesamt</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-ui-success">{{ $stats['completed'] }}</div>
                            <div class="text-xs text-surface-500">Abgeschlossen</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-brand-primary">{{ $stats['enrolled'] + $stats['attended'] }}</div>
                            <div class="text-xs text-surface-500">Eingeschrieben</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-surface-400">{{ $stats['total'] - $stats['completed'] - $stats['enrolled'] - $stats['attended'] }}</div>
                            <div class="text-xs text-surface-500">Offen</div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="lg:w-48 flex-shrink-0">
                        @php $pct = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0; @endphp
                        <div class="text-sm font-medium text-brand-dark text-center mb-1">{{ $pct }}% abgeschlossen</div>
                        <div class="progress-bar">
                            <div class="progress-bar-fill progress-bar-success" style="width: {{ $pct }}%"></div>
                        </div>
                        @if($nextLevel)
                        <div class="text-xs text-surface-500 text-center mt-1">Nächstes Level: {{ $nextLevel->title }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @else
        <x-alert type="info" title="Kein Karrierepfad zugewiesen">
            Dein People Manager hat dir noch keine Karrierestufe zugewiesen. Bitte wende dich an deinen Vorgesetzten.
        </x-alert>
        @endif

        {{-- Timeline Link --}}
        @if($enrollmentsByModule->isNotEmpty())
        <div class="flex justify-end">
            <a href="{{ route('academy.timeline') }}" class="btn-secondary btn-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Meine Timeline anzeigen
            </a>
        </div>
        @endif

        {{-- Module List --}}
        <div>
            <h2 class="text-2xl font-bold text-brand-dark mb-4">Deine Module</h2>

            @if($modules->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($modules as $module)
                @php
                    $enrollment = $enrollmentsByModule->get($module->id);
                    $status = $enrollment?->status ?? 'open';

                    $statusConfig = match($status) {
                        'completed' => ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-ui-success', 'bg' => 'bg-ui-success-light', 'label' => 'Abgeschlossen', 'badge' => 'badge-success'],
                        'attended' => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color' => 'text-ui-warning', 'bg' => 'bg-ui-warning-light', 'label' => 'Quiz offen', 'badge' => 'badge-warning'],
                        'enrolled' => ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-brand-primary', 'bg' => 'bg-brand-primary-light', 'label' => 'Gebucht', 'badge' => 'badge-primary'],
                        default => ['icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'color' => 'text-surface-400', 'bg' => 'bg-surface-100', 'label' => 'Offen', 'badge' => 'badge-neutral'],
                    };

                    $methodName = $module->method?->name;
                    $bookedSession = $enrollment?->trainingSession;

                    $stepBooking = in_array($status, ['enrolled', 'attended', 'completed']) ? 'done' : 'active';
                    $stepAttendance = match(true) {
                        in_array($status, ['attended', 'completed']) => 'done',
                        $status === 'enrolled' => 'active',
                        default => 'pending',
                    };
                    $stepQuiz = match(true) {
                        $status === 'completed' => 'done',
                        $status === 'attended' => 'active',
                        default => 'pending',
                    };
                @endphp

                <div class="card-tool hover:shadow-tool-md transition-shadow">
                    <div class="card-tool-body">
                        {{-- Status Icon & Type --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 rounded-lg {{ $statusConfig['bg'] }} flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 {{ $statusConfig['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statusConfig['icon'] }}"></path>
                                    </svg>
                                </div>
                                <span class="{{ $statusConfig['badge'] }}">{{ $statusConfig['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                @if($methodName)
                                <span class="badge-primary">{{ $methodName }}</span>
                                @endif
                                @if($module->is_mandatory)
                                <span class="badge-error">Pflicht</span>
                                @endif
                            </div>
                        </div>

                        {{-- Title & Description --}}
                        <h3 class="font-semibold text-brand-dark text-lg mb-1">{{ $module->title }}</h3>
                        @if($module->description)
                        <p class="text-sm text-surface-500 mb-3 line-clamp-2">{{ $module->description }}</p>
                        @endif

                        {{-- Next Session --}}
                        @php $nextSession = $module->trainingSessions->where('start_at', '>', now())->sortBy('start_at')->first(); @endphp
                        @if($nextSession)
                        <div class="flex items-center gap-2 text-xs text-surface-500 mb-3">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>{{ $nextSession->start_at->format('d.m.Y, H:i') }} Uhr</span>
                            @if($nextSession->location)
                            <span>&middot; {{ $nextSession->location }}</span>
                            @endif
                        </div>
                        @endif
                    </div>

                    {{-- Stepper Footer --}}
                    <div class="card-tool-footer" x-data="{ showCancel: false, showRebook: false }">
                        {{-- Step Indicator --}}
                        <div class="flex items-center gap-1 mb-3">
                            @foreach([
                                ['key' => 'booking', 'state' => $stepBooking, 'label' => 'Termin'],
                                ['key' => 'attendance', 'state' => $stepAttendance, 'label' => 'Teilnahme'],
                                ['key' => 'quiz', 'state' => $stepQuiz, 'label' => 'Quiz'],
                            ] as $i => $step)
                                @if($i > 0)
                                <div class="flex-1 h-0.5 {{ $step['state'] === 'done' || ($i === 1 && $stepBooking === 'done') ? 'bg-ui-success' : 'bg-surface-200' }}"></div>
                                @endif
                                <div class="flex flex-col items-center gap-0.5">
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                                        {{ $step['state'] === 'done' ? 'bg-ui-success text-white' : ($step['state'] === 'active' ? 'bg-brand-primary text-white' : 'bg-surface-200 text-surface-400') }}">
                                        @if($step['state'] === 'done')
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @else
                                            {{ $i + 1 }}
                                        @endif
                                    </div>
                                    <span class="text-[10px] {{ $step['state'] === 'done' ? 'text-ui-success' : ($step['state'] === 'active' ? 'text-brand-primary' : 'text-surface-400') }} font-medium">{{ $step['label'] }}</span>
                                </div>
                            @endforeach
                        </div>

                        {{-- Step Content --}}
                        @if($status === 'open')
                            @if($nextSession)
                            <form method="POST" action="{{ route('enroll') }}">
                                @csrf
                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                <input type="hidden" name="training_session_id" value="{{ $nextSession->id }}">
                                <button type="submit" class="btn-primary w-full btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Termin buchen &ndash; {{ $nextSession->start_at->format('d.m.Y, H:i') }}
                                </button>
                            </form>
                            @else
                            <div class="space-y-1.5">
                                <p class="text-xs text-surface-500">Aktuell nicht buchbar &ndash; es sind noch keine Termine geplant.</p>
                                @php $accountable = $module->getAccountableFor($user); @endphp
                                @if($accountable)
                                <p class="text-xs text-surface-500">
                                    Ansprechpartner: <a href="mailto:{{ $accountable->email }}" class="font-medium text-brand-primary hover:underline">{{ $accountable->name }}</a>
                                </p>
                                @endif
                            </div>
                            @endif

                        @elseif($status === 'enrolled')
                            <div class="space-y-2">
                                {{-- Booked Session Info --}}
                                @if($bookedSession)
                                <div class="flex items-center gap-2 text-xs bg-brand-primary-light rounded-lg px-3 py-2">
                                    <svg class="w-3.5 h-3.5 text-brand-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-brand-primary font-medium">
                                        {{ $bookedSession->start_at->format('d.m.Y, H:i') }} Uhr
                                        @if($bookedSession->location) &middot; {{ $bookedSession->location }} @endif
                                    </span>
                                </div>
                                @endif
                                <p class="text-xs text-surface-400">Warte auf Teilnahme-Bestätigung durch Trainer.</p>

                                {{-- Actions: Cancel / Rebook --}}
                                <div class="flex items-center gap-2">
                                    @if($nextSession && $bookedSession && $nextSession->id !== $bookedSession->id)
                                    <button x-show="!showRebook" @click="showRebook = true" class="btn-secondary btn-xs flex-1">Umbuchen</button>
                                    <div x-show="showRebook" x-cloak x-transition class="flex items-center gap-1 flex-1">
                                        <form method="POST" action="{{ route('enrollment.rebook', $enrollment) }}" class="flex-1">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="module_id" value="{{ $module->id }}">
                                            <input type="hidden" name="training_session_id" value="{{ $nextSession->id }}">
                                            <button type="submit" class="btn-primary btn-xs w-full">{{ $nextSession->start_at->format('d.m.') }}</button>
                                        </form>
                                        <button @click="showRebook = false" class="btn-secondary btn-xs">Nein</button>
                                    </div>
                                    @endif

                                    <button x-show="!showCancel" @click="showCancel = true" class="btn-danger btn-xs">Stornieren</button>
                                    <div x-show="showCancel" x-cloak x-transition class="flex items-center gap-1">
                                        <form method="POST" action="{{ route('enrollment.cancel', $enrollment) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-danger btn-xs">Ja, stornieren</button>
                                        </form>
                                        <button @click="showCancel = false" class="btn-secondary btn-xs">Nein</button>
                                    </div>
                                </div>
                            </div>

                        @elseif($status === 'attended')
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-xs bg-ui-success-light rounded-lg px-3 py-2">
                                    <svg class="w-3.5 h-3.5 text-ui-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-ui-success font-medium">Teilnahme bestätigt{{ $enrollment->attendance_confirmed_at ? ' am ' . $enrollment->attendance_confirmed_at->format('d.m.Y') : '' }}</span>
                                </div>
                                @if($module->quiz)
                                <a href="{{ route('quiz.show', $module->quiz) }}" class="btn-primary w-full btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Quiz starten
                                </a>
                                @else
                                <p class="text-xs text-surface-400">Kein Quiz fuer dieses Modul hinterlegt.</p>
                                @endif
                            </div>

                        @elseif($status === 'completed')
                            <div class="flex items-center gap-2 text-sm text-ui-success font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Abgeschlossen
                                @if($enrollment?->completed_at)
                                <span class="text-xs text-surface-400">{{ $enrollment->completed_at->format('d.m.Y') }}</span>
                                @endif
                            </div>

                        @elseif($status === 'cancelled')
                            <div class="flex items-center gap-2 text-sm text-ui-error font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Storniert
                                @if($enrollment?->cancelled_at)
                                <span class="text-xs text-surface-400">{{ $enrollment->cancelled_at->format('d.m.Y') }}</span>
                                @endif
                            </div>

                        @else
                            <p class="text-xs text-surface-400">Status: {{ $status }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="card-tool">
                <div class="card-tool-body">
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        <div class="empty-state-title">Keine Module verfügbar</div>
                        <div class="empty-state-description">Für deinen aktuellen Karrierepfad sind noch keine Module hinterlegt.</div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
