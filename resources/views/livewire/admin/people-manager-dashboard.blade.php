@section('page-title', 'Budget-Ampel')

<div class="space-y-6">
    {{-- Header mit Filter --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Budget-Ampel</h1>
            <p class="text-sm text-gray-500 mt-1">
                @if($showAllTeams ?? false)
                    Unternehmensweite Übersicht aller Teams
                @else
                    Übersicht deiner Teams
                @endif
            </p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="selectedYear"
                    class="rounded-lg border-gray-300 text-sm py-2 px-3 focus:border-primary-500 focus:ring-primary-500 font-medium">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
            <select wire:model.live="selectedTeamId"
                    class="rounded-lg border-gray-300 text-sm py-2 px-3 focus:border-primary-500 focus:ring-primary-500">
                <option value="">Alle meine Teams</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}{{ $team->id === -1 ? ' (Direkte Reports)' : '' }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Budget-Zusammenfassung --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-primary-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">
                    @if($showAllTeams ?? false)
                        Weiterbildungsbudget (alle Teams)
                    @else
                        Weiterbildungsbudget meiner Teams
                    @endif
                </h3>
                <p class="text-sm text-gray-500 mt-1">Jahr {{ $selectedYear }} · Persönliche Ziele</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">{{ $budgetSummary['team_count'] }} Teams</p>
                <p class="text-sm text-gray-500">{{ $budgetSummary['employee_count'] }} Mitarbeiter</p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-3xl font-bold {{ $budgetSummary['remaining'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($budgetSummary['remaining'], 0, ',', '.') }} €
                </p>
                <p class="text-sm text-gray-500 mt-1">verbleibend</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-2xl font-semibold text-blue-900">{{ number_format($budgetSummary['total_budget'], 0, ',', '.') }} €</p>
                <p class="text-sm text-blue-600">Gesamtbudget</p>
                <p class="text-xs text-blue-500 mt-1">{{ $budgetSummary['employee_count'] }} Mitarbeiter</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                <p class="text-2xl font-semibold text-green-900">{{ number_format($budgetSummary['total_spent'], 0, ',', '.') }} €</p>
                <p class="text-sm text-green-600">Ausgegeben</p>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                <p class="text-2xl font-semibold text-purple-900">{{ $budgetSummary['percentage'] }}%</p>
                <p class="text-sm text-purple-600">Auslastung</p>
            </div>
        </div>

        {{-- Fortschrittsbalken --}}
        @php
            $barPercentage = min($budgetSummary['percentage'], 100);
            $barColor = $budgetSummary['percentage'] > 100 ? 'bg-red-500' : 'bg-teal-500';
        @endphp
        <div class="relative">
            <div class="overflow-hidden h-3 rounded-full bg-gray-200">
                <div class="h-3 rounded-full {{ $barColor }} transition-all duration-500" style="width: {{ $barPercentage }}%"></div>
            </div>
            <div class="flex justify-between text-sm text-gray-600 mt-2">
                <span>{{ number_format($budgetSummary['total_spent'], 0, ',', '.') }} € genutzt</span>
                <span class="font-medium">{{ number_format($budgetSummary['total_budget'], 0, ',', '.') }} € Budget</span>
            </div>
        </div>
    </div>

    {{-- Team & Service Development Übersicht --}}
    @if(isset($budgetOverview['service_dev_summary']) && $budgetOverview['service_dev_summary']['planned_budget'] > 0)
        @php $serviceDev = $budgetOverview['service_dev_summary']; @endphp
        <div class="bg-white rounded-xl shadow-sm border-2 border-purple-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">Team & Service Development</h3>
                    <p class="text-sm text-gray-500 mt-1">Teamziele + Interne Schulungen + Sonstiges</p>
                </div>
                <div class="text-right">
                    <span class="text-sm bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-semibold">
                        Planbudget: {{ number_format($serviceDev['planned_budget'], 0, ',', '.') }} €
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                    <p class="text-2xl font-semibold text-purple-900">{{ number_format($serviceDev['team_goals'], 0, ',', '.') }} €</p>
                    <p class="text-sm text-purple-600">Teamziele</p>
                </div>
                <div class="bg-teal-50 rounded-lg p-4 border border-teal-100">
                    <p class="text-2xl font-semibold text-teal-900">{{ number_format($serviceDev['internal_training'], 0, ',', '.') }} €</p>
                    <p class="text-sm text-teal-600">Interne Schulungen</p>
                </div>
                <div class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                    <p class="text-2xl font-semibold text-amber-900">{{ number_format($serviceDev['other'], 0, ',', '.') }} €</p>
                    <p class="text-sm text-amber-600">Sonstiges</p>
                </div>
                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($serviceDev['total_spent'], 0, ',', '.') }} €</p>
                    <p class="text-sm text-gray-600">Gesamt ausgegeben</p>
                </div>
            </div>

            @php
                $sdBarPercentage = min($serviceDev['percentage'], 100);
                $sdBarColor = $serviceDev['percentage'] > 100 ? 'bg-red-500' : 'bg-purple-500';
            @endphp
            <div class="relative">
                <div class="overflow-hidden h-2 rounded-full bg-gray-200">
                    <div class="h-2 rounded-full {{ $sdBarColor }} transition-all duration-500" style="width: {{ $sdBarPercentage }}%"></div>
                </div>
                <div class="flex justify-between text-sm text-gray-600 mt-2">
                    <span>{{ number_format($serviceDev['total_spent'], 0, ',', '.') }} € genutzt</span>
                    <span class="font-medium">{{ number_format($serviceDev['planned_budget'], 0, ',', '.') }} € geplant</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Team-Karten mit Budget-Details --}}
    @if($teamSummaries->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white">
                <h2 class="font-semibold text-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Meine Teams ({{ $teamSummaries->count() }})
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($teamSummaries as $teamData)
                        @php
                            $ampelColor = match($teamData['weiterbildung']['ampel']) {
                                'green' => 'bg-green-500',
                                'yellow' => 'bg-yellow-500',
                                'red' => 'bg-red-500',
                                default => 'bg-gray-400',
                            };
                            $isHeadOfTeam = ($teamData['team_id'] ?? 0) === -1;
                        @endphp
                        @if($isHeadOfTeam)
                            {{-- Virtual Head-Ofs Team Card (not clickable, filters via dropdown) --}}
                            <div wire:click="$set('selectedTeamId', -1)"
                                 class="block p-4 bg-gradient-to-br from-purple-50 to-purple-100 border-2 border-purple-300 rounded-xl hover:border-purple-500 hover:shadow-md transition-all cursor-pointer group">
                                {{-- Header --}}
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <span class="w-3 h-3 rounded-full {{ $ampelColor }}"></span>
                                        <h3 class="font-semibold text-purple-900 group-hover:text-purple-700">
                                            {{ $teamData['team_name'] }}
                                        </h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-200 text-purple-800">
                                            Direkte Reports
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-purple-600">{{ $teamData['employee_count'] }} Head-Ofs</span>
                                    </div>
                                </div>
                                
                                {{-- Weiterbildung Fortschritt --}}
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-purple-700">Weiterbildungsbudget</span>
                                        <span class="font-semibold {{ $teamData['weiterbildung']['percentage'] > 100 ? 'text-red-600' : ($teamData['weiterbildung']['percentage'] >= 75 ? 'text-green-600' : 'text-yellow-600') }}">
                                            {{ $teamData['weiterbildung']['percentage'] }}%
                                        </span>
                                    </div>
                                    <div class="overflow-hidden h-1.5 rounded-full bg-purple-200">
                                        <div class="h-1.5 rounded-full {{ $teamData['weiterbildung']['percentage'] > 100 ? 'bg-red-500' : 'bg-purple-500' }} transition-all duration-500" 
                                             style="width: {{ min($teamData['weiterbildung']['percentage'], 100) }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-purple-500 mt-0.5">
                                        {{ number_format($teamData['weiterbildung']['spent'], 0, ',', '.') }} € / {{ number_format($teamData['weiterbildung']['budget'], 0, ',', '.') }} €
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('admin.team-budget', $teamData['team_id']) }}"
                               class="block p-4 bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 rounded-xl hover:border-primary-400 hover:shadow-md transition-all group">
                                {{-- Header --}}
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-3">
                                        <span class="w-3 h-3 rounded-full {{ $ampelColor }}"></span>
                                        <h3 class="font-semibold text-gray-900 group-hover:text-primary-700">
                                            {{ $teamData['team_name'] }}
                                        </h3>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-500">{{ $teamData['employee_count'] }} MA</span>
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                                
                                {{-- Weiterbildung Fortschritt --}}
                                <div class="mb-2">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-gray-600">Weiterbildung</span>
                                        <span class="font-semibold {{ $teamData['weiterbildung']['percentage'] > 100 ? 'text-red-600' : ($teamData['weiterbildung']['percentage'] >= 75 ? 'text-green-600' : 'text-yellow-600') }}">
                                            {{ $teamData['weiterbildung']['percentage'] }}%
                                        </span>
                                    </div>
                                    <div class="overflow-hidden h-1.5 rounded-full bg-gray-200">
                                        <div class="h-1.5 rounded-full {{ $teamData['weiterbildung']['percentage'] > 100 ? 'bg-red-500' : 'bg-teal-500' }} transition-all duration-500" 
                                             style="width: {{ min($teamData['weiterbildung']['percentage'], 100) }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-gray-400 mt-0.5">
                                        {{ number_format($teamData['weiterbildung']['spent'], 0, ',', '.') }} € / {{ number_format($teamData['weiterbildung']['budget'], 0, ',', '.') }} €
                                    </div>
                                </div>

                                {{-- Team & Service Dev --}}
                                @if(isset($teamData['service_dev']))
                                <div>
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="text-gray-600">Team & Service Dev</span>
                                        @if(($teamData['service_dev']['budget'] ?? 0) > 0)
                                            <span class="font-semibold {{ $teamData['service_dev']['percentage'] > 100 ? 'text-red-600' : ($teamData['service_dev']['percentage'] >= 75 ? 'text-purple-600' : 'text-gray-600') }}">
                                                {{ $teamData['service_dev']['percentage'] }}%
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </div>
                                    @if(($teamData['service_dev']['budget'] ?? 0) > 0)
                                        <div class="overflow-hidden h-1.5 rounded-full bg-gray-200">
                                            <div class="h-1.5 rounded-full bg-purple-500 transition-all duration-500" 
                                                 style="width: {{ min($teamData['service_dev']['percentage'], 100) }}%"></div>
                                        </div>
                                        <div class="text-[10px] text-gray-400 mt-0.5">
                                            {{ number_format($teamData['service_dev']['spent'], 0, ',', '.') }} € / {{ number_format($teamData['service_dev']['budget'], 0, ',', '.') }} €
                                        </div>
                                    @else
                                        <div class="text-[10px] text-gray-400">
                                            {{ number_format($teamData['service_dev']['spent'] ?? 0, 0, ',', '.') }} € (kein Budget definiert)
                                        </div>
                                    @endif
                                </div>
                                @endif
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Schnell-Übersicht Karten (kompakt) --}}
    <div class="flex flex-wrap gap-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 px-4 py-2 flex items-center gap-2">
            <span class="w-3 h-3 rounded-full bg-slate-400"></span>
            <span class="text-lg font-bold text-gray-900">{{ $grouped['counts']['total'] }}</span>
            <span class="text-xs text-gray-500">Gesamt</span>
        </div>
        <button wire:click="$set('filterAmpel', 'red')" class="bg-white rounded-lg shadow-sm border-2 {{ ($filterAmpel ?? '') === 'red' ? 'border-red-500 ring-2 ring-red-200' : 'border-red-200' }} px-4 py-2 flex items-center gap-2 hover:bg-red-50 transition-colors cursor-pointer">
            <span class="w-3 h-3 rounded-full bg-red-500"></span>
            <span class="text-lg font-bold text-red-600">{{ $grouped['counts']['red'] ?? 0 }}</span>
            <span class="text-xs text-gray-500">Eskalation</span>
        </button>
        <button wire:click="$set('filterAmpel', 'yellow')" class="bg-white rounded-lg shadow-sm border-2 {{ ($filterAmpel ?? '') === 'yellow' ? 'border-yellow-500 ring-2 ring-yellow-200' : 'border-yellow-200' }} px-4 py-2 flex items-center gap-2 hover:bg-yellow-50 transition-colors cursor-pointer">
            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
            <span class="text-lg font-bold text-yellow-600">{{ $grouped['counts']['yellow'] ?? 0 }}</span>
            <span class="text-xs text-gray-500">Achtung</span>
        </button>
        <button wire:click="$set('filterAmpel', 'green')" class="bg-white rounded-lg shadow-sm border-2 {{ ($filterAmpel ?? '') === 'green' ? 'border-green-500 ring-2 ring-green-200' : 'border-green-200' }} px-4 py-2 flex items-center gap-2 hover:bg-green-50 transition-colors cursor-pointer">
            <span class="w-3 h-3 rounded-full bg-green-500"></span>
            <span class="text-lg font-bold text-green-600">{{ $grouped['counts']['green'] ?? 0 }}</span>
            <span class="text-xs text-gray-500">Top</span>
        </button>
        <button wire:click="$set('filterAmpel', 'orange')" class="bg-white rounded-lg shadow-sm border-2 {{ ($filterAmpel ?? '') === 'orange' ? 'border-orange-500 ring-2 ring-orange-200' : 'border-orange-200' }} px-4 py-2 flex items-center gap-2 hover:bg-orange-50 transition-colors cursor-pointer">
            <span class="w-3 h-3 rounded-full bg-orange-500"></span>
            <span class="text-lg font-bold text-orange-600">{{ $grouped['counts']['orange'] ?? 0 }}</span>
            <span class="text-xs text-gray-500">Über Budget</span>
        </button>
        <button wire:click="$set('filterAmpel', 'gray')" class="bg-white rounded-lg shadow-sm border-2 {{ ($filterAmpel ?? '') === 'gray' ? 'border-gray-500 ring-2 ring-gray-200' : 'border-gray-300' }} px-4 py-2 flex items-center gap-2 hover:bg-gray-50 transition-colors cursor-pointer">
            <span class="w-3 h-3 rounded-full bg-gray-400"></span>
            <span class="text-lg font-bold text-gray-600">{{ $grouped['counts']['gray'] ?? 0 }}</span>
            <span class="text-xs text-gray-500">Kein Budget</span>
        </button>
        @if($filterAmpel ?? false)
            <button wire:click="$set('filterAmpel', null)" class="bg-gray-100 rounded-lg px-4 py-2 flex items-center gap-2 hover:bg-gray-200 transition-colors cursor-pointer text-sm text-gray-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Filter zurücksetzen
            </button>
        @endif
    </div>

    {{-- DEINE TO-DOS Sektion --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-slate-700 to-slate-800 text-white">
            <h2 class="font-semibold text-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Deine To-Dos
            </h2>
        </div>

        {{-- ROT: Eskalation --}}
        @if(($grouped['counts']['red'] ?? 0) > 0 && (!$filterAmpel || $filterAmpel === 'red'))
            <div class="border-b border-gray-200">
                <div class="px-6 py-3 bg-red-50 border-b border-red-100">
                    <h3 class="font-semibold text-red-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        ESKALATION ({{ $grouped['counts']['red'] }} Mitarbeiter)
                        <span class="text-xs font-normal text-red-600 ml-2">Dringend Gespräch führen</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grouped['red'] as $employee)
                        <a href="{{ route('admin.employee-budget', $employee['user_id']) }}" 
                           class="flex items-center justify-between px-6 py-4 hover:bg-red-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 {{ ($employee['is_head_of'] ?? false) ? 'bg-purple-100 ring-2 ring-purple-400' : 'bg-red-100' }} rounded-full flex items-center justify-center {{ ($employee['is_head_of'] ?? false) ? 'text-purple-700' : 'text-red-700' }} font-medium">
                                    {{ substr($employee['user_name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-900 group-hover:text-red-700">{{ $employee['user_name'] }}</p>
                                        @if($employee['is_head_of'] ?? false)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700">Head-Of</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $employee['team_name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-red-600">{{ number_format($employee['utilization_rate'], 0) }}%</p>
                                    <p class="text-xs text-gray-500">{{ number_format($employee['actual_hours'] ?? 0, 1, ',', '.') }}h / {{ number_format($employee['planned_hours'] ?? 0, 1, ',', '.') }}h</p>
                                </div>
                                <div class="w-48 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $employee['action_text'] }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- GELB: Stunden nutzen --}}
        @if(($grouped['counts']['yellow'] ?? 0) > 0 && (!$filterAmpel || $filterAmpel === 'yellow'))
            <div class="border-b border-gray-200">
                <div class="px-6 py-3 bg-yellow-50 border-b border-yellow-100">
                    <h3 class="font-semibold text-yellow-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                        STUNDEN NUTZEN ({{ $grouped['counts']['yellow'] }} Mitarbeiter)
                        <span class="text-xs font-normal text-yellow-600 ml-2">Achtung</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grouped['yellow'] as $employee)
                        <a href="{{ route('admin.employee-budget', $employee['user_id']) }}" 
                           class="flex items-center justify-between px-6 py-4 hover:bg-yellow-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 {{ ($employee['is_head_of'] ?? false) ? 'bg-purple-100 ring-2 ring-purple-400' : 'bg-yellow-100' }} rounded-full flex items-center justify-center {{ ($employee['is_head_of'] ?? false) ? 'text-purple-700' : 'text-yellow-700' }} font-medium">
                                    {{ substr($employee['user_name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-900 group-hover:text-yellow-700">{{ $employee['user_name'] }}</p>
                                        @if($employee['is_head_of'] ?? false)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700">Head-Of</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $employee['team_name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($employee['utilization_rate'], 0) }}%</p>
                                    <p class="text-xs text-gray-500">{{ number_format($employee['actual_hours'] ?? 0, 1, ',', '.') }}h / {{ number_format($employee['planned_hours'] ?? 0, 1, ',', '.') }}h</p>
                                </div>
                                <div class="w-48 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ $employee['action_text'] }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- GRÜN: Top Stundennutzung --}}
        @if(($grouped['counts']['green'] ?? 0) > 0 && (!$filterAmpel || $filterAmpel === 'green'))
            <div class="border-b border-gray-200">
                <div class="px-6 py-3 bg-green-50 border-b border-green-100">
                    <h3 class="font-semibold text-green-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        TOP STUNDENNUTZUNG ({{ $grouped['counts']['green'] }} Mitarbeiter)
                        <span class="text-xs font-normal text-green-600 ml-2">90-110%</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grouped['green'] as $employee)
                        <a href="{{ route('admin.employee-budget', $employee['user_id']) }}" 
                           class="flex items-center justify-between px-6 py-4 hover:bg-green-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 {{ ($employee['is_head_of'] ?? false) ? 'bg-purple-100 ring-2 ring-purple-400' : 'bg-green-100' }} rounded-full flex items-center justify-center {{ ($employee['is_head_of'] ?? false) ? 'text-purple-700' : 'text-green-700' }} font-medium">
                                    {{ substr($employee['user_name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-900 group-hover:text-green-700">{{ $employee['user_name'] }}</p>
                                        @if($employee['is_head_of'] ?? false)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700">Head-Of</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $employee['team_name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-green-600">{{ number_format($employee['utilization_rate'], 0) }}%</p>
                                    <p class="text-xs text-gray-500">{{ number_format($employee['actual_hours'] ?? 0, 1, ',', '.') }}h / {{ number_format($employee['planned_hours'] ?? 0, 1, ',', '.') }}h</p>
                                </div>
                                <div class="w-48 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $employee['action_text'] }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ORANGE: Über Budget --}}
        @if(($grouped['counts']['orange'] ?? 0) > 0 && (!$filterAmpel || $filterAmpel === 'orange'))
            <div>
                <div class="px-6 py-3 bg-orange-50 border-b border-orange-100">
                    <h3 class="font-semibold text-orange-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                        ÜBER BUDGET ({{ $grouped['counts']['orange'] }} Mitarbeiter)
                        <span class="text-xs font-normal text-orange-600 ml-2">Bitte einchecken</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grouped['orange'] as $employee)
                        <a href="{{ route('admin.employee-budget', $employee['user_id']) }}" 
                           class="flex items-center justify-between px-6 py-4 hover:bg-orange-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 {{ ($employee['is_head_of'] ?? false) ? 'bg-purple-100 ring-2 ring-purple-400' : 'bg-orange-100' }} rounded-full flex items-center justify-center {{ ($employee['is_head_of'] ?? false) ? 'text-purple-700' : 'text-orange-700' }} font-medium">
                                    {{ substr($employee['user_name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-900 group-hover:text-orange-700">{{ $employee['user_name'] }}</p>
                                        @if($employee['is_head_of'] ?? false)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700">Head-Of</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $employee['team_name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-orange-600">{{ number_format($employee['utilization_rate'], 0) }}%</p>
                                    <p class="text-xs text-gray-500">{{ number_format($employee['actual_hours'] ?? 0, 1, ',', '.') }}h / {{ number_format($employee['planned_hours'] ?? 0, 1, ',', '.') }}h</p>
                                </div>
                                <div class="w-48 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        {{ $employee['action_text'] }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- GRAU: Kein Budget --}}
        @if(($grouped['counts']['gray'] ?? 0) > 0 && (!$filterAmpel || $filterAmpel === 'gray'))
            <div>
                <div class="px-6 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="font-semibold text-gray-700 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                        KEIN BUDGET ({{ $grouped['counts']['gray'] }} Mitarbeiter)
                        <span class="text-xs font-normal text-gray-500 ml-2">Aktuell keine Budgets hinterlegt</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grouped['gray'] as $employee)
                        <a href="{{ route('admin.employee-budget', $employee['user_id']) }}" 
                           class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 {{ ($employee['is_head_of'] ?? false) ? 'bg-purple-100 ring-2 ring-purple-400' : 'bg-gray-100' }} rounded-full flex items-center justify-center {{ ($employee['is_head_of'] ?? false) ? 'text-purple-700' : 'text-gray-600' }} font-medium">
                                    {{ substr($employee['user_name'], 0, 1) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <p class="font-medium text-gray-900 group-hover:text-gray-700">{{ $employee['user_name'] }}</p>
                                        @if($employee['is_head_of'] ?? false)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 text-purple-700">Head-Of</span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-500">{{ $employee['team_name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-gray-500">-</p>
                                    <p class="text-xs text-gray-500">0h / 0h</p>
                                </div>
                                <div class="w-48 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                        {{ $employee['action_text'] }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Keine Mitarbeiter --}}
        @if($grouped['counts']['total'] === 0)
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Mitarbeiter</h3>
                <p class="mt-1 text-sm text-gray-500">Es sind dir noch keine Mitarbeiter zugeordnet.</p>
            </div>
        @endif
    </div>

    {{-- Ampel-Legende --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <span class="font-medium text-gray-700">Ampel-Legende (Stundennutzung YTD):</span>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-orange-500"></span>
                <span class="text-gray-600">&gt; 110% - Über Budget, bitte einchecken</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                <span class="text-gray-600">90-110% - Top Stundennutzung</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                <span class="text-gray-600">60-90% - Stunden nutzen, Achtung</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <span class="text-gray-600">&lt; 60% - Eskalation, Gespräch führen</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-gray-400"></span>
                <span class="text-gray-600">Kein Budget hinterlegt</span>
            </div>
        </div>
    </div>
</div>
