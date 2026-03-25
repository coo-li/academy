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
        <div>
            <a href="{{ route('dashboard') }}" class="text-sm text-surface-500 hover:text-brand-primary mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
                Zurück zur Übersicht
            </a>
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">
                        @if($module->careerLevel?->careerPath?->emoji)
                            <span class="mr-2">{{ $module->careerLevel->careerPath->emoji }}</span>
                        @endif
                        {{ $module->title }}
                    </h1>
                    <div class="flex flex-wrap gap-2 mt-2">
                        @php
                            $statusConfig = match($status) {
                                'completed' => ['label' => 'Abgeschlossen', 'badge' => 'badge-success'],
                                'attended' => ['label' => 'Quiz offen', 'badge' => 'badge-accent'],
                                'enrolled' => ['label' => 'Gebucht', 'badge' => 'badge-primary'],
                                'cancelled' => ['label' => 'Storniert', 'badge' => 'badge-danger'],
                                default => ['label' => 'Offen', 'badge' => 'badge-neutral'],
                            };
                        @endphp
                        <span class="{{ $statusConfig['badge'] }}">{{ $statusConfig['label'] }}</span>
                        @if($module->method)
                            <span class="badge-primary">{{ $module->method->name }}</span>
                        @endif
                        @if($module->skillCategory)
                            <span class="badge-neutral">{{ $module->skillCategory->name }}</span>
                        @endif
                        @if($module->is_mandatory)
                            <span class="badge-accent">Pflicht</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Stepper --}}
        @php
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
        <div class="card-tool">
            <div class="card-tool-body">
                <div class="flex items-center justify-center gap-2 py-2">
                    @foreach([
                        ['key' => 'booking', 'state' => $stepBooking, 'label' => 'Termin buchen'],
                        ['key' => 'attendance', 'state' => $stepAttendance, 'label' => 'Teilnahme bestätigen'],
                        ['key' => 'quiz', 'state' => $stepQuiz, 'label' => 'Quiz absolvieren'],
                    ] as $i => $step)
                        @if($i > 0)
                        <div class="flex-1 max-w-24 h-0.5 rounded-full {{ $step['state'] === 'done' || ($i === 1 && $stepBooking === 'done') ? 'bg-ui-success' : 'bg-surface-200' }} transition-colors duration-500"></div>
                        @endif
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-300
                                {{ $step['state'] === 'done' ? 'bg-ui-success text-white shadow-sm' : ($step['state'] === 'active' ? 'bg-brand-primary text-white shadow-sm shadow-brand-primary/30' : 'bg-surface-100 text-surface-400') }}">
                                @if($step['state'] === 'done')
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                @else
                                    {{ $i + 1 }}
                                @endif
                            </div>
                            <span class="text-sm {{ $step['state'] === 'done' ? 'text-ui-success font-semibold' : ($step['state'] === 'active' ? 'text-brand-primary font-semibold' : 'text-surface-400') }}">{{ $step['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main Content (left 2/3) --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Description --}}
                @if($module->description)
                <x-card title="Beschreibung">
                    <div class="prose prose-sm max-w-none text-surface-600 leading-relaxed">
                        {!! nl2br(e($module->description)) !!}
                    </div>
                </x-card>
                @endif

                {{-- Upcoming Sessions --}}
                <x-card title="Termine">
                    @if($upcomingSessions->isNotEmpty())
                    <div class="divide-y divide-surface-200">
                        @foreach($upcomingSessions as $session)
                        @php
                            $enrolledCount = $session->enrollments->whereIn('status', ['enrolled', 'attended', 'completed'])->count();
                            $isFull = $session->max_participants && $enrolledCount >= $session->max_participants;
                            $isBookedSession = $bookedSession && $bookedSession->id === $session->id;
                        @endphp
                        <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-brand-primary-light flex flex-col items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-brand-primary leading-none">{{ $session->start_at->format('d') }}</span>
                                    <span class="text-[10px] text-brand-primary uppercase">{{ $session->start_at->translatedFormat('M') }}</span>
                                </div>
                                <div>
                                    <div class="font-medium text-brand-dark">{{ $session->start_at->format('d.m.Y') }}</div>
                                    <div class="text-xs text-surface-500">{{ $session->start_at->format('H:i') }} – {{ $session->end_at->format('H:i') }} Uhr</div>
                                    @if($session->location)
                                    <div class="text-xs text-surface-400 mt-0.5">{{ $session->location }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-3 flex-shrink-0">
                                <span class="text-xs text-surface-500">{{ $enrolledCount }}{{ $session->max_participants ? '/'.$session->max_participants : '' }} Teilnehmer</span>
                                @if($isBookedSession)
                                    <span class="badge-primary">Dein Termin</span>
                                @elseif($status === 'open' && !$isFull)
                                    <form method="POST" action="{{ route('enroll') }}">
                                        @csrf
                                        <input type="hidden" name="module_id" value="{{ $module->id }}">
                                        <input type="hidden" name="training_session_id" value="{{ $session->id }}">
                                        <button type="submit" class="btn-primary btn-xs">Buchen</button>
                                    </form>
                                @elseif($isFull)
                                    <span class="badge-neutral">Ausgebucht</span>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-sm text-surface-500 text-center py-4">
                        Aktuell sind keine Termine geplant.
                    </div>
                    @endif
                </x-card>

                {{-- Participants (only when enrolled) --}}
                @if($bookedSession && $sessionParticipants->isNotEmpty())
                <x-card title="Teilnehmende deines Termins">
                    <div class="flex flex-wrap gap-2">
                        @foreach($sessionParticipants as $participant)
                        <div class="flex items-center gap-2 bg-surface-50 rounded-lg px-3 py-2">
                            <div class="w-7 h-7 rounded-full bg-brand-primary-light flex items-center justify-center">
                                <span class="text-xs font-bold text-brand-primary">{{ mb_strtoupper(mb_substr($participant->name, 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                            </div>
                            <span class="text-sm text-brand-dark">{{ $participant->name }}</span>
                        </div>
                        @endforeach
                    </div>
                </x-card>
                @endif

                {{-- Training Materials (only after attendance confirmed) --}}
                @if($canViewMaterials)
                <x-card title="Schulungsunterlagen">
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
                                    @elseif(str_starts_with($material->mime_type ?? '', 'image/'))
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
                                    <div class="text-sm font-medium text-brand-dark truncate">{{ $material->displayName() }}</div>
                                    @if($material->isFile())
                                        <div class="text-xs text-surface-500">{{ number_format($material->file_size / 1024, 0) }} KB</div>
                                    @endif
                                </div>
                            </div>
                            @if($material->isLink())
                                <a href="{{ $material->url }}" target="_blank" rel="noopener noreferrer" class="btn-secondary btn-xs flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                    Öffnen
                                </a>
                            @else
                                <a href="{{ route('portfolio.material.download', $material) }}" class="btn-secondary btn-xs flex-shrink-0">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                    Download
                                </a>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-sm text-surface-500 text-center py-4">
                        Keine Unterlagen vorhanden.
                    </div>
                    @endif
                </x-card>
                @endif
            </div>

            {{-- Sidebar (right 1/3) --}}
            <div class="space-y-6">
                {{-- Actions --}}
                <x-card title="Aktionen">
                    <div class="space-y-3" x-data="{ showCancel: false, showRebook: false }">
                        @if($status === 'open')
                            @php $nextSession = $upcomingSessions->first(); @endphp
                            @if($nextSession)
                            <form method="POST" action="{{ route('enroll') }}">
                                @csrf
                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                <input type="hidden" name="training_session_id" value="{{ $nextSession->id }}">
                                <button type="submit" class="btn-primary w-full">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Nächsten Termin buchen
                                </button>
                            </form>
                            <p class="text-xs text-surface-400 text-center">{{ $nextSession->start_at->format('d.m.Y, H:i') }} Uhr</p>
                            @else
                            <p class="text-sm text-surface-500">Aktuell nicht buchbar &ndash; es sind noch keine Termine geplant.</p>
                            @endif

                        @elseif($status === 'enrolled')
                            @if($bookedSession)
                            <div class="flex items-center gap-2 text-sm bg-brand-primary-light rounded-lg px-3 py-2.5">
                                <svg class="w-4 h-4 text-brand-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="text-brand-primary font-medium">
                                    {{ $bookedSession->start_at->format('d.m.Y, H:i') }} Uhr
                                    @if($bookedSession->location) &middot; {{ $bookedSession->location }} @endif
                                </span>
                            </div>
                            @endif
                            <p class="text-xs text-surface-400">Warte auf Teilnahme-Bestätigung durch Trainer.</p>

                            @php $nextSession = $upcomingSessions->first(); @endphp
                            @if($nextSession && $bookedSession && $nextSession->id !== $bookedSession->id)
                            <button x-show="!showRebook" @click="showRebook = true" class="btn-secondary w-full">Umbuchen</button>
                            <div x-show="showRebook" x-cloak x-transition class="space-y-2">
                                <form method="POST" action="{{ route('enrollment.rebook', $enrollment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="module_id" value="{{ $module->id }}">
                                    <input type="hidden" name="training_session_id" value="{{ $nextSession->id }}">
                                    <button type="submit" class="btn-primary w-full btn-sm">Umbuchen auf {{ $nextSession->start_at->format('d.m.Y, H:i') }}</button>
                                </form>
                                <button @click="showRebook = false" class="btn-secondary w-full btn-sm">Abbrechen</button>
                            </div>
                            @endif

                            <button x-show="!showCancel" @click="showCancel = true" class="btn-danger w-full">Stornieren</button>
                            <div x-show="showCancel" x-cloak x-transition class="space-y-2">
                                <form method="POST" action="{{ route('enrollment.cancel', $enrollment) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn-danger w-full btn-sm">Ja, stornieren</button>
                                </form>
                                <button @click="showCancel = false" class="btn-secondary w-full btn-sm">Abbrechen</button>
                            </div>

                        @elseif($status === 'attended')
                            <div class="flex items-center gap-2 text-sm bg-ui-success-light rounded-lg px-3 py-2.5">
                                <svg class="w-4 h-4 text-ui-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-ui-success font-medium">Teilnahme bestätigt{{ $enrollment->attendance_confirmed_at ? ' am ' . $enrollment->attendance_confirmed_at->format('d.m.Y') : '' }}</span>
                            </div>
                            @if($module->quiz)
                            <a href="{{ route('quiz.show', $module->quiz) }}" class="btn-primary w-full">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                Quiz starten
                            </a>
                            @else
                            <p class="text-xs text-surface-400">Kein Quiz für dieses Modul hinterlegt.</p>
                            @endif

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
                            @php $nextSession = $upcomingSessions->first(); @endphp
                            @if($nextSession)
                            <form method="POST" action="{{ route('enroll') }}">
                                @csrf
                                <input type="hidden" name="module_id" value="{{ $module->id }}">
                                <input type="hidden" name="training_session_id" value="{{ $nextSession->id }}">
                                <button type="submit" class="btn-primary w-full">
                                    Erneut buchen
                                </button>
                            </form>
                            @endif
                        @endif
                    </div>
                </x-card>

                {{-- Contact --}}
                @if($accountable)
                <x-card title="Ansprechpartner">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-brand-primary-light flex items-center justify-center flex-shrink-0">
                            <span class="text-sm font-bold text-brand-primary">{{ mb_strtoupper(mb_substr($accountable->name, 0, 1, 'UTF-8'), 'UTF-8') }}</span>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-brand-dark">{{ $accountable->name }}</div>
                            <a href="mailto:{{ $accountable->email }}" class="text-xs text-brand-primary hover:underline">{{ $accountable->email }}</a>
                        </div>
                    </div>
                </x-card>
                @endif

                {{-- Module Info --}}
                <x-card title="Details">
                    <dl class="space-y-3 text-sm">
                        @if($module->method)
                        <div class="flex justify-between">
                            <dt class="text-surface-500">Methode</dt>
                            <dd class="font-medium text-brand-dark">{{ $module->method->name }}</dd>
                        </div>
                        @endif
                        @if($module->skillCategory)
                        <div class="flex justify-between">
                            <dt class="text-surface-500">Kategorie</dt>
                            <dd class="font-medium text-brand-dark">{{ $module->skillCategory->name }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-surface-500">Pflichtmodul</dt>
                            <dd class="font-medium text-brand-dark">{{ $module->is_mandatory ? 'Ja' : 'Nein' }}</dd>
                        </div>
                        @if($module->careerLevel)
                        <div class="flex justify-between">
                            <dt class="text-surface-500">Karrierepfad</dt>
                            <dd class="font-medium text-brand-dark">{{ $module->careerLevel->careerPath?->name }}</dd>
                        </div>
                        @endif
                    </dl>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
