<x-app-layout>
    @section('page-title', 'Meine Timeline')

    {{-- #region agent log --}}
    @php
        $logData = json_encode(['sessionId'=>'f85c1f','hypothesisId'=>'H2','location'=>'timeline.blade.php:top','message'=>'New timeline blade rendered','data'=>['hasActiveEnrollments'=>isset($activeEnrollments),'hasHistoryEnrollments'=>isset($historyEnrollments),'activeCount'=>isset($activeEnrollments)?$activeEnrollments->count():'N/A','historyCount'=>isset($historyEnrollments)?$historyEnrollments->count():'N/A'],'timestamp'=>round(microtime(true)*1000)]);
        file_put_contents(base_path('.cursor/debug-f85c1f.log'), $logData."\n", FILE_APPEND);
    @endphp
    {{-- #endregion --}}

    <div class="space-y-6">
        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Meine Timeline</h1>
                <p class="text-surface-500 mt-1">Übersicht aller deiner Buchungen und Fortschritte.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="btn-secondary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Zurück zum Dashboard
            </a>
        </div>

        {{-- Tabs --}}
        <div x-data="{ activeTab: 'active' }">
            <div class="tabs">
                <button @click="activeTab = 'active'"
                        :class="activeTab === 'active' ? 'tab-active' : 'tab'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Aktive Buchungen
                    @if($activeEnrollments->isNotEmpty())
                        <span class="badge-primary ml-1">{{ $activeEnrollments->count() }}</span>
                    @endif
                </button>
                <button @click="activeTab = 'history'"
                        :class="activeTab === 'history' ? 'tab-active' : 'tab'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Historie
                    @if($historyEnrollments->isNotEmpty())
                        <span class="badge-neutral ml-1">{{ $historyEnrollments->count() }}</span>
                    @endif
                </button>
            </div>

            {{-- Tab: Aktive Buchungen --}}
            <div x-show="activeTab === 'active'" x-transition>
                @if($activeEnrollments->isNotEmpty())
                <div class="timeline mt-6">
                    @foreach($activeEnrollments as $enrollment)
                        @include('academy.partials.timeline-item', ['enrollment' => $enrollment])
                    @endforeach
                </div>
                @else
                <div class="card-tool mt-6">
                    <div class="card-tool-body">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="empty-state-title">Keine aktiven Buchungen</div>
                            <div class="empty-state-description">Du hast aktuell keine offenen Buchungen. Gehe zum Dashboard, um dich für ein Modul einzuschreiben.</div>
                            <a href="{{ route('dashboard') }}" class="btn-primary mt-4">Zum Dashboard</a>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Tab: Historie --}}
            <div x-show="activeTab === 'history'" x-cloak x-transition>
                @if($historyEnrollments->isNotEmpty())
                <div class="timeline mt-6">
                    @foreach($historyEnrollments as $enrollment)
                        @include('academy.partials.timeline-item', ['enrollment' => $enrollment])
                    @endforeach
                </div>
                @else
                <div class="card-tool mt-6">
                    <div class="card-tool-body">
                        <div class="empty-state">
                            <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="empty-state-title">Noch keine Historie</div>
                            <div class="empty-state-description">Hier erscheinen abgeschlossene und stornierte Buchungen.</div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
