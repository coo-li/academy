<div class="space-y-6">
    {{-- Header (nur Titel, ohne Filter) --}}
    <div>
        @if($viewingOther)
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.dashboard.team') }}" class="text-surface-500 hover:text-brand-dark">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <span class="text-sm text-surface-500">Budget-Status von</span>
            </div>
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">{{ $targetUserName }}</h1>
        @else
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Mein Budgetstatus</h1>
        @endif
    </div>

    {{-- WEITERBILDUNGSBUDGET - Persönliche Entwicklung --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-primary-200 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 px-6 py-4 border-b border-primary-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Weiterbildungsbudget</h3>
                        <p class="text-xs text-white/70">Budget für eigene Entwicklung · {{ $selectedPeriodName }} {{ $selectedYear }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-sm bg-white text-primary-700 px-3 py-1.5 rounded-lg font-semibold shadow-sm">
                        {{ number_format($weiterbildungData['full_year_allowance'], 0, ',', '.') }} € / Jahr
                    </span>
                    @if($selectedPeriod !== 'year')
                        <p class="text-xs text-white/70 mt-1">{{ number_format($weiterbildungData['total_allowance'], 0, ',', '.') }} € für Zeitraum</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6">
            @php
                $ringPercentage = min($weiterbildungData['percentage'], 100);
                $ringColor = $weiterbildungData['percentage'] > 100 ? '#ef4444' : '#00B3C7';
                $circumference = 2 * 3.14159 * 54;
                $dashOffset = $circumference - ($circumference * $ringPercentage / 100);

                $verfuegbarPercentage = $weiterbildungData['total_allowance'] > 0
                    ? max(0, min(100, ($weiterbildungData['remaining'] / $weiterbildungData['total_allowance']) * 100))
                    : 0;
                $verfuegbarColor = $weiterbildungData['remaining'] < 0 ? '#ef4444' : '#6366f1';
                $verfuegbarDashOffset = $circumference - ($circumference * $verfuegbarPercentage / 100);
            @endphp

            {{-- Hauptbereich: Zwei Ringe nebeneinander --}}
            <div class="flex items-center justify-center gap-12 mb-6">
                {{-- Ring 1: Verwendungsgrad --}}
                <div class="flex flex-col items-center">
                    <div class="relative w-36 h-36">
                        <svg class="w-36 h-36 transform -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="54" fill="none" stroke="#262626" stroke-width="10"/>
                            <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $ringColor }}" stroke-width="10"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $dashOffset }}"
                                    class="transition-all duration-1000 ease-out"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-3xl font-black {{ $weiterbildungData['percentage'] > 100 ? 'text-red-500' : 'text-dark-tuerkis' }}">
                                {{ number_format($weiterbildungData['percentage'], 0) }}%
                            </span>
                        </div>
                    </div>
                    <span class="mt-2 text-sm font-bold text-gray-600 uppercase tracking-wider">Verwendet</span>
                    <span class="text-xs text-gray-400">{{ number_format($weiterbildungData['total_allowance'] - $weiterbildungData['remaining'], 0, ',', '.') }} € von {{ number_format($weiterbildungData['total_allowance'], 0, ',', '.') }} €</span>
                </div>

                {{-- Ring 2: Verfügbares Budget --}}
                <div class="flex flex-col items-center">
                    <div class="relative w-36 h-36">
                        <svg class="w-36 h-36 transform -rotate-90" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="54" fill="none" stroke="#262626" stroke-width="10"/>
                            <circle cx="60" cy="60" r="54" fill="none" stroke="{{ $verfuegbarColor }}" stroke-width="10"
                                    stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}"
                                    stroke-dashoffset="{{ $verfuegbarDashOffset }}"
                                    class="transition-all duration-1000 ease-out"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-2xl font-black {{ $weiterbildungData['remaining'] < 0 ? 'text-red-500' : 'text-indigo-600' }}">
                                {{ number_format($weiterbildungData['remaining'], 0, ',', '.') }} €
                            </span>
                        </div>
                    </div>
                    <span class="mt-2 text-sm font-bold text-gray-600 uppercase tracking-wider">Verfügbar</span>
                    <span class="text-xs text-gray-400">{{ number_format($verfuegbarPercentage, 0) }}% vom Budget übrig</span>
                </div>
            </div>

            {{-- Sub-Blöcke: Persönliche Ziele + Externe Schulungen --}}
            <div class="grid md:grid-cols-2 gap-4">
                {{-- Persönliche Ziele --}}
                <button type="button" wire:click="setTab('weiterbildung')"
                        class="text-left p-4 rounded-lg border-2 transition-all hover:shadow-md
                               {{ $activeTab === 'weiterbildung' ? 'bg-primary-50 border-primary-400 ring-2 ring-primary-200' : 'bg-white border-gray-200 hover:border-primary-300' }}">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-primary-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">Persönliche Ziele</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Anzahl Ziele:</span>
                            <span class="font-medium text-gray-900">{{ $categoryStats['personal_goals']['count'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Geplant:</span>
                            <span class="font-medium text-blue-600">{{ number_format($categoryStats['personal_goals']['soll_hours'], 1, ',', '.') }} h</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Genutzt:</span>
                            <span class="font-medium text-green-600">{{ number_format($categoryStats['personal_goals']['ist_hours'], 1, ',', '.') }} h</span>
                        </div>
                        @if(($categoryStats['personal_goals']['soll_hours'] ?? 0) > 0)
                            <div class="mt-2">
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-gray-500">Fortschritt</span>
                                    <span class="font-medium text-gray-700">{{ number_format($categoryStats['personal_goals']['verwendung'], 0) }}%</span>
                                </div>
                                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-primary-500 rounded-full" style="width: {{ min($categoryStats['personal_goals']['verwendung'], 100) }}%"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="mt-3 text-xs text-primary-600 font-medium flex items-center gap-1">
                        Details anzeigen
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>

                {{-- Externe Schulungen --}}
                <button type="button" wire:click="setTab('externe')"
                        class="text-left p-4 rounded-lg border-2 transition-all hover:shadow-md
                               {{ $activeTab === 'externe' ? 'bg-cyan-50 border-cyan-400 ring-2 ring-cyan-200' : 'bg-white border-gray-200 hover:border-cyan-300' }}">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-cyan-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">Externe Schulungen</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Buchungen:</span>
                            <span class="font-medium text-gray-900">{{ $weiterbildungData['training_count'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Stunden:</span>
                            <span class="font-medium text-blue-600">{{ number_format($weiterbildungData['training_hours'] ?? 0, 1, ',', '.') }} h</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Kosten:</span>
                            <span class="font-bold text-cyan-700">{{ number_format($weiterbildungData['training_costs_euros'] ?? 0, 0, ',', '.') }} €</span>
                        </div>
                        @if($weiterbildungData['has_cash_limit'] ?? false)
                            <div class="mt-2 p-2 bg-orange-50 rounded text-xs">
                                <span class="text-orange-700">Cash-Limit: {{ number_format($weiterbildungData['cash_remaining'] ?? 0, 0, ',', '.') }} € verbleibend</span>
                            </div>
                        @endif
                    </div>
                    <div class="mt-3 text-xs text-cyan-600 font-medium flex items-center gap-1">
                        Details anzeigen
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>
            </div>
        </div>
    </div>

    {{-- TEAM & SERVICE DEVELOPMENT --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-purple-200 overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 border-b border-purple-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Team & Service Development</h3>
                        <p class="text-xs text-white/70">Teamziele, interne Schulungen & Sonstiges · {{ $selectedPeriodName }} {{ $selectedYear }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-xs text-white/80 bg-white/20 px-3 py-1 rounded-full">Wird nicht vom eigenen Budget abgezogen</span>
                </div>
            </div>
        </div>

        <div class="p-6">
            {{-- Sub-Blöcke: Teamziele + Interne Schulungen + Sonstiges --}}
            <div class="grid md:grid-cols-3 gap-4">
                {{-- Teamziele --}}
                <button type="button" wire:click="setTab('teamziele')"
                        class="text-left p-4 rounded-lg border-2 transition-all hover:shadow-md
                               {{ $activeTab === 'teamziele' ? 'bg-purple-50 border-purple-400 ring-2 ring-purple-200' : 'bg-white border-gray-200 hover:border-purple-300' }}">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">Teamziele</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Anzahl:</span>
                            <span class="font-medium text-gray-900">{{ $categoryStats['team_goals']['count'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Genutzt:</span>
                            <span class="font-medium text-green-600">{{ number_format($categoryStats['team_goals']['ist_hours'], 1, ',', '.') }} h</span>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-purple-600 font-medium flex items-center gap-1">
                        Details
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>

                {{-- Interne Schulungen --}}
                <button type="button" wire:click="setTab('schulungen')"
                        class="text-left p-4 rounded-lg border-2 transition-all hover:shadow-md
                               {{ $activeTab === 'schulungen' ? 'bg-teal-50 border-teal-400 ring-2 ring-teal-200' : 'bg-white border-gray-200 hover:border-teal-300' }}">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-teal-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">Interne Schulungen</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Anzahl:</span>
                            <span class="font-medium text-gray-900">{{ $categoryStats['internal_training']['count'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Genutzt:</span>
                            <span class="font-medium text-green-600">{{ number_format($categoryStats['internal_training']['ist_hours'], 1, ',', '.') }} h</span>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-teal-600 font-medium flex items-center gap-1">
                        Details
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>

                {{-- Sonstiges --}}
                <button type="button" wire:click="setTab('sonstiges')"
                        class="text-left p-4 rounded-lg border-2 transition-all hover:shadow-md
                               {{ $activeTab === 'sonstiges' ? 'bg-amber-50 border-amber-400 ring-2 ring-amber-200' : 'bg-white border-gray-200 hover:border-amber-300' }}">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-8 h-8 bg-amber-100 rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <span class="font-semibold text-gray-900">Sonstiges</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Anzahl:</span>
                            <span class="font-medium text-gray-900">{{ $categoryStats['other']['count'] }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Genutzt:</span>
                            <span class="font-medium text-green-600">{{ number_format($categoryStats['other']['ist_hours'], 1, ',', '.') }} h</span>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-amber-600 font-medium flex items-center gap-1">
                        Details
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                </button>
            </div>
        </div>
    </div>

    {{-- Filter-Bereich - NUR auf Detail-Tabs anzeigen --}}
    @if($activeTab !== 'uebersicht')
        <div class="flex items-center justify-center">
            <div class="flex items-center gap-3 bg-white rounded-lg shadow-sm border border-gray-200 p-2">
                {{-- Jahr-Dropdown --}}
                <select wire:model.live="selectedYear"
                        class="rounded border-gray-300 text-sm py-1 px-2 focus:border-primary-500 focus:ring-primary-500 font-medium">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>

                <div class="h-6 w-px bg-gray-300"></div>

                {{-- Zeitraum-Buttons --}}
                <div class="flex gap-1">
                    <button type="button" wire:click="setPeriod('year')"
                            class="px-3 py-1.5 text-xs font-semibold rounded transition-colors
                                   {{ $selectedPeriod === 'year' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Jahr
                    </button>
                    <button type="button" wire:click="setPeriod('q1')"
                            class="px-2 py-1.5 text-xs font-medium rounded transition-colors
                                   {{ $selectedPeriod === 'q1' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Q1
                    </button>
                    <button type="button" wire:click="setPeriod('q2')"
                            class="px-2 py-1.5 text-xs font-medium rounded transition-colors
                                   {{ $selectedPeriod === 'q2' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Q2
                    </button>
                    <button type="button" wire:click="setPeriod('q3')"
                            class="px-2 py-1.5 text-xs font-medium rounded transition-colors
                                   {{ $selectedPeriod === 'q3' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Q3
                    </button>
                    <button type="button" wire:click="setPeriod('q4')"
                            class="px-2 py-1.5 text-xs font-medium rounded transition-colors
                                   {{ $selectedPeriod === 'q4' ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100' }}">
                        Q4
                    </button>
                </div>

                <div class="h-6 w-px bg-gray-300"></div>

                {{-- Monat-Dropdown --}}
                <select wire:model.live="selectedPeriod"
                        class="rounded border-gray-300 text-sm py-1 px-2 focus:border-primary-500 focus:ring-primary-500
                               {{ in_array($selectedPeriod, ['1','2','3','4','5','6','7','8','9','10','11','12']) ? 'font-medium text-primary-700' : 'text-gray-500' }}">
                    <option value="year" {{ !in_array($selectedPeriod, ['1','2','3','4','5','6','7','8','9','10','11','12']) ? 'disabled' : '' }}>Monat...</option>
                    @foreach($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    {{-- Tabs mit visueller Gruppierung --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 overflow-x-auto">
            <nav class="flex -mb-px min-w-max items-end">
                {{-- Jahresübersicht --}}
                <button type="button" wire:click="setTab('uebersicht')"
                        class="px-5 py-3 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'uebersicht' ? 'border-gray-900 text-gray-900 bg-gray-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Jahresübersicht
                </button>

                {{-- Trenner --}}
                <div class="h-8 w-px bg-gray-200 mx-1"></div>

                {{-- WEITERBILDUNG Gruppe --}}
                <div class="flex items-end">
                    <span class="px-2 py-1 text-[10px] font-semibold text-primary-600 uppercase tracking-wider bg-primary-50 rounded-t border-t border-x border-primary-200">Weiterbildung</span>
                </div>
                <button type="button" wire:click="setTab('weiterbildung')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'weiterbildung' ? 'border-primary-500 text-primary-600 bg-primary-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Pers. Ziele
                    @if($categoryStats['personal_goals']['count'] > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $activeTab === 'weiterbildung' ? 'bg-primary-200 text-primary-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $categoryStats['personal_goals']['count'] }}
                        </span>
                    @endif
                </button>
                <button type="button" wire:click="setTab('externe')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'externe' ? 'border-cyan-500 text-cyan-600 bg-cyan-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Ext. Schulungen
                    @if($trainingBookings->count() > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $activeTab === 'externe' ? 'bg-cyan-200 text-cyan-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $trainingBookings->count() }}
                        </span>
                    @endif
                </button>

                {{-- Trenner --}}
                <div class="h-8 w-px bg-gray-200 mx-1"></div>

                {{-- TEAM DEVELOPMENT Gruppe --}}
                <div class="flex items-end">
                    <span class="px-2 py-1 text-[10px] font-semibold text-purple-600 uppercase tracking-wider bg-purple-50 rounded-t border-t border-x border-purple-200">Team Dev</span>
                </div>
                <button type="button" wire:click="setTab('teamziele')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'teamziele' ? 'border-purple-500 text-purple-600 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Teamziele
                    @if($categoryStats['team_goals']['count'] > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $activeTab === 'teamziele' ? 'bg-purple-200 text-purple-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $categoryStats['team_goals']['count'] }}
                        </span>
                    @endif
                </button>
                <button type="button" wire:click="setTab('schulungen')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'schulungen' ? 'border-teal-500 text-teal-600 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Int. Schulungen
                    @if($categoryStats['internal_training']['count'] > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $activeTab === 'schulungen' ? 'bg-teal-200 text-teal-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $categoryStats['internal_training']['count'] }}
                        </span>
                    @endif
                </button>
                <button type="button" wire:click="setTab('sonstiges')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'sonstiges' ? 'border-amber-500 text-amber-600 bg-amber-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Sonstiges
                    @if($categoryStats['other']['count'] > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $activeTab === 'sonstiges' ? 'bg-amber-200 text-amber-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $categoryStats['other']['count'] }}
                        </span>
                    @endif
                </button>

                {{-- Archiv --}}
                <button type="button" wire:click="setTab('archiv')"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'archiv' ? 'border-orange-500 text-orange-600 bg-orange-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Archiv
                    @if($archivedCount > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full {{ $activeTab === 'archiv' ? 'bg-orange-200 text-orange-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $archivedCount }}
                        </span>
                    @endif
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            @if($activeTab === 'uebersicht')
                {{-- NEUE JAHRESÜBERSICHT MIT MATRIX --}}
                @include('livewire.partials.budget-matrix', ['monthlyBreakdown' => $monthlyBreakdown, 'hourlyRate' => $hourlyRate])
            @elseif($activeTab === 'weiterbildung')
                @include('livewire.partials.budget-entries-table', ['entries' => $entriesByType['personal_goals'], 'emptyMessage' => 'Keine persönlichen Ziele für ' . $selectedPeriodName . ' ' . $selectedYear, 'hourlyRate' => $hourlyRate])
            @elseif($activeTab === 'teamziele')
                @include('livewire.partials.budget-entries-table', ['entries' => $entriesByType['team_goals'], 'emptyMessage' => 'Keine Teamziele für ' . $selectedPeriodName . ' ' . $selectedYear, 'hourlyRate' => $hourlyRate])
            @elseif($activeTab === 'schulungen')
                @include('livewire.partials.budget-entries-table', ['entries' => $entriesByType['internal_training'], 'emptyMessage' => 'Keine internen Schulungen für ' . $selectedPeriodName . ' ' . $selectedYear, 'hourlyRate' => $hourlyRate])
            @elseif($activeTab === 'sonstiges')
                @include('livewire.partials.budget-entries-table', ['entries' => $entriesByType['other'], 'emptyMessage' => 'Keine sonstigen Einträge für ' . $selectedPeriodName . ' ' . $selectedYear, 'hourlyRate' => $hourlyRate])
            @elseif($activeTab === 'externe')
                {{-- Externe Schulungen / Training Bookings --}}
                @if($trainingBookings->isNotEmpty())
                    <div class="mb-4 p-4 bg-cyan-50 border border-cyan-200 rounded-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-cyan-800">Externe Weiterbildungen</p>
                                <p class="text-xs text-cyan-600">Gebuchte externe Schulungen und Zertifizierungen. Diese Kosten werden direkt vom Weiterbildungsbudget abgezogen.</p>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weiterbildung</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kosten</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Arbeitszeit</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($trainingBookings as $booking)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $booking->created_at->format('d.m.Y') }}</td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $booking->name }}</div>
                                            @if($booking->notes)
                                                <div class="text-xs text-gray-500 mt-1">{{ Str::limit($booking->notes, 50) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                            {{ number_format($booking->net_cost, 2, ',', '.') }} €
                                            @if($booking->requires_gross_billing)
                                                <span class="block text-xs text-yellow-600">Brutto</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($booking->during_work_hours && $booking->hours)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ number_format($booking->hours, 1, ',', '.') }} h
                                                </span>
                                            @else
                                                <span class="text-gray-400">–</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($booking->budget_entry_created)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Gebucht
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                    Offen
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 text-sm font-semibold text-gray-700">Summe</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                                        {{ number_format($trainingBookings->sum('net_cost'), 2, ',', '.') }} €
                                    </td>
                                    <td class="px-4 py-3 text-center text-sm font-medium text-gray-700">
                                        @php $totalHours = $trainingBookings->where('during_work_hours', true)->sum('hours'); @endphp
                                        @if($totalHours > 0)
                                            {{ number_format($totalHours, 1, ',', '.') }} h
                                        @else
                                            –
                                        @endif
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <p class="text-lg font-medium">Keine externen Schulungen</p>
                        <p class="text-sm mt-1">Für {{ $selectedPeriodName }} {{ $selectedYear }} wurden keine externen Weiterbildungen gebucht.</p>
                    </div>
                @endif
            @elseif($activeTab === 'archiv')
                @if($archivedEntries->isNotEmpty())
                    <div class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                        <p class="text-sm text-orange-700">
                            Diese Einträge wurden archiviert und fließen nicht mehr in die Berechnung ein.
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Budget</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Betrag</th>
                                    @if($canDeleteArchived)
                                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktion</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($archivedEntries as $entry)
                                    <tr class="bg-orange-50/50">
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $entry->date->format('d.m.Y') }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $entry->budget_name ?? $entry->label }}</td>
                                        <td class="px-4 py-3 text-sm text-right font-medium text-gray-700">
                                            {{ number_format($entry->amount, 0, ',', '.') }} €
                                        </td>
                                        @if($canDeleteArchived)
                                            <td class="px-4 py-3 text-right">
                                                <button type="button" wire:click="deleteArchivedEntry({{ $entry->id }})"
                                                        wire:confirm="Diesen archivierten Eintrag endgültig löschen?"
                                                        class="text-red-600 hover:text-red-800 text-sm">
                                                    Löschen
                                                </button>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-12 text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                        </svg>
                        <p class="text-lg font-medium">Keine archivierten Einträge</p>
                    </div>
                @endif
            @endif
        </div>
    </div>

</div>
