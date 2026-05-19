<x-app-layout>
    @section('page-title', 'Meine td-Schulungen')

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
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Meine td-Schulungen</h1>
                <p class="text-surface-500 mt-1.5 text-base">Willkommen zurück, {{ $user->name }}!</p>
            </div>
            @if($careerLevel)
            <div class="flex items-center gap-3">
                <span class="badge-primary text-sm px-3 py-1">{{ $careerPath?->name }}</span>
                <span class="text-lg font-bold font-display text-brand-dark">{{ $careerLevel->title }}</span>
            </div>
            @endif
        </div>

        {{-- Career Level Overview --}}
        @if($careerLevel)
        <div class="card-tool overflow-hidden">
            <div class="bg-gradient-to-r from-brand-primary-light via-white to-brand-accent-light/30 p-6 lg:p-8">
                <div class="flex flex-col lg:flex-row lg:items-center gap-8">
                    {{-- Level Info --}}
                    <div class="flex items-center gap-5 flex-shrink-0">
                        <div class="w-20 h-20 rounded-2xl bg-white shadow-tool-md flex items-center justify-center">
                            <span class="text-3xl font-extrabold font-display text-brand-primary">{{ $careerLevel->level_number }}</span>
                        </div>
                        <div>
                            <div class="text-xs text-surface-500 uppercase tracking-widest font-semibold mb-1">Dein Upskilling Plan</div>
                            <div class="text-2xl font-extrabold font-display text-brand-dark">{{ $careerLevel->title }}</div>
                            <div class="text-sm text-surface-500 mt-0.5">{{ $careerPath?->name }}</div>
                        </div>
                    </div>

                    {{-- Progress Stats --}}
                    <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="text-center p-3 rounded-xl bg-white/70">
                            <div class="text-2xl font-extrabold font-display text-brand-dark">{{ $stats['total'] }}</div>
                            <div class="text-xs text-surface-500 font-medium mt-0.5">Module gesamt</div>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-white/70">
                            <div class="text-2xl font-extrabold font-display text-ui-success">{{ $stats['completed'] }}</div>
                            <div class="text-xs text-surface-500 font-medium mt-0.5">Abgeschlossen</div>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-white/70">
                            <div class="text-2xl font-extrabold font-display text-brand-primary">{{ $stats['enrolled'] + $stats['attended'] }}</div>
                            <div class="text-xs text-surface-500 font-medium mt-0.5">Eingeschrieben</div>
                        </div>
                        <div class="text-center p-3 rounded-xl bg-white/70">
                            <div class="text-2xl font-extrabold font-display text-surface-400">{{ $stats['total'] - $stats['completed'] - $stats['enrolled'] - $stats['attended'] }}</div>
                            <div class="text-xs text-surface-500 font-medium mt-0.5">Offen</div>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="lg:w-56 flex-shrink-0">
                        @php $pct = $stats['total'] > 0 ? round(($stats['completed'] / $stats['total']) * 100) : 0; @endphp
                        <div class="text-sm font-bold font-display text-brand-dark text-center mb-2">{{ $pct }}% abgeschlossen</div>
                        <div class="progress-bar">
                            <div class="progress-bar-fill {{ $pct >= 80 ? 'bg-ui-success' : ($pct >= 40 ? 'bg-brand-accent' : 'bg-brand-primary') }}" style="width: {{ $pct }}%"></div>
                        </div>
                        @if($nextLevel)
                        <div class="text-xs text-surface-500 text-center mt-2 flex items-center justify-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-brand-accent" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                            <span>Nächstes Level: <strong class="text-brand-dark">{{ $nextLevel->title }}</strong></span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @elseif($modules->isNotEmpty())
        <x-alert type="info" title="Kein Karrierepfad zugeordnet" :dismissible="true">
            Dir wurde noch kein Karrierepfad zugeordnet. Deine individuell zugewiesenen Module findest du weiter unten.
        </x-alert>
        @else
        <x-alert type="warning" title="Noch keine Module zugewiesen">
            Dir wurden noch keine Module zugewiesen. Bitte wende dich an deinen People Manager.
        </x-alert>
        @endif

        {{-- Upcoming Termine --}}
        @if($upcomingTermine->isNotEmpty())
        <div x-data="{ scrollEl: null }" x-init="scrollEl = $refs.terminScroll">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-brand-primary-light flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-extrabold text-brand-dark">Deine nächsten Termine</h2>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="scrollEl.scrollBy({ left: -300, behavior: 'smooth' })"
                        class="w-8 h-8 rounded-lg bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors hidden sm:flex">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </button>
                    <button @click="scrollEl.scrollBy({ left: 300, behavior: 'smooth' })"
                        class="w-8 h-8 rounded-lg bg-surface-100 hover:bg-surface-200 flex items-center justify-center text-surface-500 transition-colors hidden sm:flex">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </button>
                    <a href="{{ route('academy.timeline') }}" class="btn-secondary btn-sm ml-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Timeline
                    </a>
                </div>
            </div>

            <div x-ref="terminScroll" class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-3 -mx-1 px-1 scrollbar-thin scrollbar-thumb-surface-300 scrollbar-track-transparent">
                @foreach($upcomingTermine as $termin)
                @php
                    $session = $termin->trainingSession;
                    $mod = $termin->module;
                    $isAttended = $termin->status === 'attended';
                @endphp
                <a href="{{ route('academy.module.show', $mod) }}"
                    class="flex-shrink-0 w-[280px] snap-start card-tool border-l-4 {{ $isAttended ? 'border-brand-accent' : 'border-brand-primary' }} hover:shadow-card-hover hover:-translate-y-0.5 transition-all duration-200 block">
                    <div class="card-tool-body p-4">
                        <div class="flex items-start gap-3">
                            <div class="flex flex-col items-center flex-shrink-0 w-14">
                                <span class="text-3xl font-extrabold font-display {{ $isAttended ? 'text-brand-accent' : 'text-brand-primary' }} leading-none">{{ $session->start_at->format('d') }}</span>
                                <span class="text-xs font-semibold text-surface-500 uppercase tracking-wider">{{ $session->start_at->translatedFormat('M') }}</span>
                                <span class="text-[10px] text-surface-400 mt-0.5">{{ $session->start_at->format('Y') }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold font-display text-brand-dark text-sm leading-snug truncate">{{ $mod->title }}</h3>
                                <div class="flex items-center gap-1.5 mt-1.5">
                                    <svg class="w-3.5 h-3.5 text-surface-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-xs text-surface-500 font-medium">{{ $session->start_at->format('H:i') }} Uhr</span>
                                </div>
                                @if($session->location)
                                <div class="flex items-center gap-1.5 mt-1">
                                    <svg class="w-3.5 h-3.5 text-surface-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="text-xs text-surface-500 truncate">{{ $session->location }}</span>
                                </div>
                                @endif
                                <div class="mt-2">
                                    <span class="{{ $isAttended ? 'badge-accent' : 'badge-primary' }} text-[10px]">
                                        {{ $isAttended ? 'Teilnahme bestätigt' : 'Gebucht' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @elseif($enrollmentsByModule->isNotEmpty())
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
        <div x-data="{
                typeFilter: 'all',
                pathFilter: 'all',
                progressFilter: 'all',
                totalModules: {{ $modules->count() }},
                get visibleCount() {
                    return document.querySelectorAll('[data-module-card]:not([style*=&quot;display: none&quot;])').length;
                },
                get hasActiveFilter() {
                    return this.typeFilter !== 'all' || this.pathFilter !== 'all' || this.progressFilter !== 'all';
                },
                resetFilters() {
                    this.typeFilter = 'all';
                    this.pathFilter = 'all';
                    this.progressFilter = 'all';
                },
                matchesFilter(mandatory, assigned, pathId, status, hasQuiz, hasFutureSession) {
                    if (this.typeFilter === 'pflicht' && !mandatory) return false;
                    if (this.typeFilter === 'wahl' && mandatory) return false;
                    if (this.typeFilter === 'assigned' && !assigned) return false;

                    if (this.pathFilter !== 'all' && pathId !== this.pathFilter) return false;

                    if (this.progressFilter !== 'all') {
                        switch (this.progressFilter) {
                            case 'open': if (status !== 'open') return false; break;
                            case 'enrolled': if (status !== 'enrolled') return false; break;
                            case 'attended': if (status !== 'attended') return false; break;
                            case 'quiz_open': if (status !== 'attended' || !hasQuiz) return false; break;
                            case 'completed': if (status !== 'completed') return false; break;
                            case 'no_termin': if (status !== 'open' || hasFutureSession) return false; break;
                        }
                    }
                    return true;
                }
            }">
            @php
                $mandatoryCount = $modules->where('is_mandatory', true)->count();
                $electiveCount = $modules->where('is_mandatory', false)->count();
                $assignedCount = count($assignedModuleIds);
            @endphp

            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-5">
                <h2 class="text-2xl font-extrabold text-brand-dark flex-shrink-0">Deine Module</h2>

                @if($modules->isNotEmpty())
                <div class="flex flex-col items-end gap-2.5">
                    {{-- Type Pills --}}
                    <div class="flex items-center gap-1.5 flex-wrap justify-end">
                        <button @click="typeFilter = 'all'"
                            :class="typeFilter === 'all' ? 'bg-brand-primary text-white shadow-sm' : 'bg-surface-100 text-surface-600 hover:bg-surface-200'"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all duration-200">
                            Alle <span class="opacity-75">{{ $modules->count() }}</span>
                        </button>
                        <button @click="typeFilter = 'pflicht'"
                            :class="typeFilter === 'pflicht' ? 'bg-brand-accent text-white shadow-sm' : 'bg-surface-100 text-surface-600 hover:bg-surface-200'"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all duration-200">
                            Pflicht <span class="opacity-75">{{ $mandatoryCount }}</span>
                        </button>
                        <button @click="typeFilter = 'wahl'"
                            :class="typeFilter === 'wahl' ? 'bg-brand-primary text-white shadow-sm' : 'bg-surface-100 text-surface-600 hover:bg-surface-200'"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all duration-200">
                            Wahl <span class="opacity-75">{{ $electiveCount }}</span>
                        </button>
                        @if($assignedCount > 0)
                        <button @click="typeFilter = 'assigned'"
                            :class="typeFilter === 'assigned' ? 'bg-purple-600 text-white shadow-sm' : 'bg-surface-100 text-surface-600 hover:bg-surface-200'"
                            class="px-2.5 py-1 rounded-md text-xs font-semibold transition-all duration-200">
                            Zugewiesen <span class="opacity-75">{{ $assignedCount }}</span>
                        </button>
                        @endif

                        <span class="w-px h-4 bg-surface-200 mx-0.5"></span>

                        {{-- Dropdowns inline --}}
                        @if($userPaths->count() > 1)
                        <select x-model="pathFilter"
                            :class="pathFilter !== 'all' ? 'border-brand-primary bg-brand-primary-light/30 text-brand-primary' : 'border-surface-200 bg-surface-50 text-surface-600'"
                            class="text-xs rounded-md py-1 pl-2 pr-6 font-semibold transition-all duration-200 focus:border-brand-primary focus:ring-brand-primary/20 cursor-pointer">
                            <option value="all">Pfad</option>
                            @foreach($userPaths as $path)
                            <option value="{{ $path->id }}">{{ $path->emoji ?? '' }} {{ $path->name }}</option>
                            @endforeach
                        </select>
                        @endif

                        <select x-model="progressFilter"
                            :class="progressFilter !== 'all' ? 'border-brand-primary bg-brand-primary-light/30 text-brand-primary' : 'border-surface-200 bg-surface-50 text-surface-600'"
                            class="text-xs rounded-md py-1 pl-2 pr-6 font-semibold transition-all duration-200 focus:border-brand-primary focus:ring-brand-primary/20 cursor-pointer">
                            <option value="all">Fortschritt</option>
                            <option value="open">Offen</option>
                            <option value="no_termin">Ohne Termin</option>
                            <option value="enrolled">Termin gebucht</option>
                            <option value="attended">Teilgenommen</option>
                            <option value="quiz_open">Quiz offen</option>
                            <option value="completed">Abgeschlossen</option>
                        </select>
                    </div>

                    {{-- Reset --}}
                    <button @click="resetFilters()" x-show="hasActiveFilter" x-transition x-cloak
                        class="flex items-center gap-1 text-xs text-surface-500 hover:text-brand-primary font-medium transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        Filter zurücksetzen
                    </button>
                </div>
                @endif
            </div>

            @if($modules->isNotEmpty())
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($modules as $module)
                @php
                    $enrollment = $enrollmentsByModule->get($module->id);
                    $status = $enrollment?->status ?? 'open';
                    $schedulingType = $module->method?->scheduling_type ?? 'scheduled';

                    $enrolledLabel = $schedulingType === 'self_study' ? 'Eingeschrieben' : 'Gebucht';

                    $statusConfig = match($status) {
                        'completed' => ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-ui-success', 'bg' => 'bg-ui-success-light', 'label' => 'Abgeschlossen', 'badge' => 'badge-success', 'border' => 'border-ui-success'],
                        'attended' => ['icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', 'color' => 'text-brand-accent', 'bg' => 'bg-brand-accent-light', 'label' => 'Quiz offen', 'badge' => 'badge-accent', 'border' => 'border-brand-accent'],
                        'enrolled' => ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-brand-primary', 'bg' => 'bg-brand-primary-light', 'label' => $enrolledLabel, 'badge' => 'badge-primary', 'border' => 'border-brand-primary'],
                        'requested' => ['icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'text-amber-600', 'bg' => 'bg-amber-50', 'label' => 'Angefragt', 'badge' => 'badge-warning', 'border' => 'border-amber-400'],
                        default => ['icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'color' => 'text-surface-400', 'bg' => 'bg-surface-100', 'label' => 'Offen', 'badge' => 'badge-neutral', 'border' => 'border-surface-200'],
                    };

                    $methodName = $module->method?->name;
                    $bookedSession = $enrollment?->trainingSession;

                    if ($schedulingType === 'self_study') {
                        $steps = [
                            ['state' => in_array($status, ['enrolled', 'attended', 'completed']) ? 'done' : 'active', 'label' => 'Einschreiben'],
                            ['state' => $status === 'completed' ? 'done' : (in_array($status, ['enrolled', 'attended']) ? 'active' : 'pending'), 'label' => 'Quiz'],
                        ];
                    } elseif ($schedulingType === 'request') {
                        $steps = [
                            ['state' => in_array($status, ['requested', 'enrolled', 'attended', 'completed']) ? 'done' : 'active', 'label' => 'Anfrage'],
                            ['state' => in_array($status, ['enrolled', 'attended', 'completed']) ? 'done' : ($status === 'requested' ? 'active' : 'pending'), 'label' => 'Termin'],
                            ['state' => in_array($status, ['attended', 'completed']) ? 'done' : ($status === 'enrolled' ? 'active' : 'pending'), 'label' => 'Teilnahme'],
                            ['state' => $status === 'completed' ? 'done' : ($status === 'attended' ? 'active' : 'pending'), 'label' => 'Quiz'],
                        ];
                    } else {
                        $steps = [
                            ['state' => in_array($status, ['enrolled', 'attended', 'completed']) ? 'done' : 'active', 'label' => 'Termin'],
                            ['state' => in_array($status, ['attended', 'completed']) ? 'done' : ($status === 'enrolled' ? 'active' : 'pending'), 'label' => 'Teilnahme'],
                            ['state' => $status === 'completed' ? 'done' : ($status === 'attended' ? 'active' : 'pending'), 'label' => 'Quiz'],
                        ];
                    }

                    $modulePathId = $module->careerLevel?->careerPath?->id ?? 'none';
                    $moduleIsAssigned = in_array($module->id, $assignedModuleIds);
                    $moduleHasQuiz = (bool) $module->quiz;
                    $moduleHasFutureSession = $module->trainingSessions->where('start_at', '>', now())->isNotEmpty();
                @endphp

                <a href="{{ route('academy.module.show', $module) }}"
                    data-module-card
                    x-show="matchesFilter({{ $module->is_mandatory ? 'true' : 'false' }}, {{ $moduleIsAssigned ? 'true' : 'false' }}, '{{ $modulePathId }}', '{{ $status }}', {{ $moduleHasQuiz ? 'true' : 'false' }}, {{ $moduleHasFutureSession ? 'true' : 'false' }})"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="card-tool border-t-4 {{ $statusConfig['border'] }} hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 opacity-0 animate-card-enter block" style="animation-delay: {{ $loop->index * 60 }}ms">
                    <div class="card-tool-body">
                        {{-- Status Icon & Type --}}
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                @php $pathEmoji = $module->careerLevel?->careerPath?->emoji; @endphp
                                <div class="w-11 h-11 rounded-xl {{ $statusConfig['bg'] }} flex items-center justify-center flex-shrink-0">
                                    @if($pathEmoji)
                                        <span class="text-2xl leading-none">{{ $pathEmoji }}</span>
                                    @else
                                        <svg class="w-5.5 h-5.5 {{ $statusConfig['color'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $statusConfig['icon'] }}"></path>
                                        </svg>
                                    @endif
                                </div>
                                <span class="{{ $statusConfig['badge'] }}">{{ $statusConfig['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($methodName)
                                <span class="badge-primary">{{ $methodName }}</span>
                                @endif
                                @if($module->is_mandatory)
                                <span class="badge-accent">Pflicht</span>
                                @else
                                <span class="badge-neutral">Wahl</span>
                                @endif
                            </div>
                        </div>

                        {{-- Title & Description --}}
                        <h3 class="font-bold font-display text-brand-dark text-xl mb-1.5">{{ $module->title }}</h3>
                        @if($module->description)
                        <p class="text-sm text-surface-500 mb-4 line-clamp-2 leading-relaxed">{{ $module->description }}</p>
                        @endif

                        {{-- Next Session / Booked Session (not for self-study) --}}
                        @if($schedulingType !== 'self_study')
                            @if($bookedSession && in_array($status, ['enrolled', 'attended']))
                            <div class="flex items-center gap-2 text-xs text-surface-500 bg-surface-50 rounded-lg px-3 py-2">
                                <svg class="w-3.5 h-3.5 text-brand-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium">{{ $bookedSession->start_at->format('d.m.Y, H:i') }} Uhr</span>
                                @if($bookedSession->location)
                                <span class="text-surface-400">&middot;</span>
                                <span>{{ $bookedSession->location }}</span>
                                @endif
                            </div>
                            @else
                                @php $nextSession = $module->trainingSessions->where('start_at', '>', now())->sortBy('start_at')->first(); @endphp
                                @if($nextSession && $status === 'open')
                                <div class="flex items-center gap-2 text-xs text-surface-500 bg-surface-50 rounded-lg px-3 py-2">
                                    <svg class="w-3.5 h-3.5 text-brand-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="font-medium">Nächster Termin: {{ $nextSession->start_at->format('d.m.Y, H:i') }} Uhr</span>
                                </div>
                                @endif
                            @endif
                        @endif
                    </div>

                    {{-- Stepper Footer --}}
                    <div class="card-tool-footer">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                @foreach($steps as $i => $step)
                                    @if($i > 0)
                                    @php $prevDone = $steps[$i - 1]['state'] === 'done'; @endphp
                                    <div class="w-6 h-0.5 rounded-full {{ $prevDone ? 'bg-ui-success' : 'bg-surface-200' }} transition-colors duration-500"></div>
                                    @endif
                                    <div class="flex flex-col items-center gap-1">
                                        <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300
                                            {{ $step['state'] === 'done' ? 'bg-ui-success text-white shadow-sm' : ($step['state'] === 'active' ? 'bg-brand-primary text-white shadow-sm shadow-brand-primary/30' : 'bg-surface-100 text-surface-400') }}">
                                            @if($step['state'] === 'done')
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                            @else
                                                {{ $i + 1 }}
                                            @endif
                                        </div>
                                        <span class="text-[10px] {{ $step['state'] === 'done' ? 'text-ui-success font-semibold' : ($step['state'] === 'active' ? 'text-brand-primary font-semibold' : 'text-surface-400') }}">{{ $step['label'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <svg class="w-5 h-5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>
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
                        <div class="empty-state-description">Es sind noch keine Module für dich hinterlegt.</div>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Interested Modules --}}
        @if($interestedModules->isNotEmpty())
        <div>
            <div class="flex items-center gap-3 mb-5">
                <h2 class="text-2xl font-extrabold text-brand-dark">Interesse bekundet</h2>
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-pink-100 text-pink-600 text-sm font-bold">{{ $interestedModules->count() }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
                @foreach($interestedModules as $module)
                @php
                    $interest = $interestsByModule->get($module->id);
                    $methodName = $module->method?->name;
                @endphp

                <a href="{{ route('academy.module.show', $module) }}" class="card-tool border-t-4 border-pink-400 hover:shadow-card-hover hover:-translate-y-1 transition-all duration-300 opacity-0 animate-card-enter block" style="animation-delay: {{ $loop->index * 60 }}ms">
                    <div class="card-tool-body">
                        {{-- Status Icon & Type --}}
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-11 h-11 rounded-xl bg-pink-50 flex items-center justify-center flex-shrink-0 text-2xl">
                                    ⏳
                                </div>
                                <span class="inline-flex items-center rounded-md bg-pink-50 px-2 py-1 text-xs font-medium text-pink-700 ring-1 ring-inset ring-pink-600/20">Interesse bekundet</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($methodName)
                                <span class="badge-primary">{{ $methodName }}</span>
                                @endif
                                @if($module->is_mandatory)
                                <span class="badge-accent">Pflicht</span>
                                @else
                                <span class="badge-neutral">Wahl</span>
                                @endif
                            </div>
                        </div>

                        {{-- Title & Description --}}
                        <h3 class="font-bold font-display text-brand-dark text-xl mb-1.5">{{ $module->title }}</h3>
                        @if($module->description)
                        <p class="text-sm text-surface-500 mb-4 line-clamp-2 leading-relaxed">{{ $module->description }}</p>
                        @endif

                        {{-- Career Path Info --}}
                        @if($module->careerLevel?->careerPath)
                        <div class="flex items-center gap-2 text-xs text-surface-500 bg-surface-50 rounded-lg px-3 py-2">
                            @if($module->careerLevel->careerPath->emoji)
                            <span class="text-base leading-none">{{ $module->careerLevel->careerPath->emoji }}</span>
                            @endif
                            <span class="font-medium">{{ $module->careerLevel->careerPath->name }}</span>
                            <span class="text-surface-400">&middot;</span>
                            <span>{{ $module->careerLevel->title }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="card-tool-footer">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-xs">
                                @if($interest?->isNoted())
                                <span class="inline-flex items-center gap-1 text-ui-success font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    Von deinem People Manager zur Kenntnis genommen
                                </span>
                                @else
                                <span class="inline-flex items-center gap-1 text-surface-400 font-medium">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Warte auf Rückmeldung
                                </span>
                                @endif
                            </div>
                            <svg class="w-5 h-5 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</x-app-layout>
