@section('page-title', 'Budget-Overview')

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Budget-Overview</h1>
            <p class="text-surface-500 mt-1.5 text-base">Unternehmensweite Übersicht</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard.plan-budgets') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Planbudgets bearbeiten
            </a>
            <select wire:model.live="selectedYear"
                    class="rounded-lg border-gray-300 text-sm py-2 px-3 focus:border-primary-500 focus:ring-primary-500 font-medium">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Weiterbildungsbudget --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-primary-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Weiterbildungsbudget</h3>
                <p class="text-sm text-gray-500 mt-1">Jahr {{ $selectedYear }} · Persönliche Ziele + Externe Schulungen</p>
            </div>
            <div class="text-right">
                <p class="text-sm text-gray-500">{{ $companyBudgetSummary['team_count'] }} Teams</p>
                <p class="text-sm text-gray-500">{{ $companyBudgetSummary['employee_count'] }} Mitarbeiter</p>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-3xl font-bold {{ $companyBudgetSummary['remaining'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($companyBudgetSummary['remaining'], 0, ',', '.') }} €
                </p>
                <p class="text-sm text-gray-500 mt-1">verbleibend</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-2xl font-semibold text-blue-900">{{ number_format($companyBudgetSummary['total_budget'], 0, ',', '.') }} €</p>
                <p class="text-sm text-blue-600">Gesamtbudget</p>
                <p class="text-xs text-blue-500 mt-1">{{ $companyBudgetSummary['employee_count'] }} Mitarbeiter</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                <p class="text-2xl font-semibold text-green-900">{{ number_format($companyBudgetSummary['personal_goals_spent'], 0, ',', '.') }} €</p>
                <p class="text-sm text-green-600">Persönliche Ziele</p>
            </div>
            @if(($companyBudgetSummary['training_count'] ?? 0) > 0)
            <div class="bg-cyan-50 rounded-lg p-4 border border-cyan-100">
                <p class="text-2xl font-semibold text-cyan-900">{{ number_format($companyBudgetSummary['training_costs'], 0, ',', '.') }} €</p>
                <p class="text-sm text-cyan-600">Externe Schulungen</p>
                <p class="text-xs text-cyan-500 mt-1">{{ $companyBudgetSummary['training_count'] }} Buchung(en)</p>
            </div>
            @endif
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                <p class="text-2xl font-semibold text-purple-900">{{ number_format($companyBudgetSummary['percentage'], 0) }}%</p>
                <p class="text-sm text-purple-600">Auslastung</p>
            </div>
        </div>

        {{-- Ladebalken --}}
        @php
            $barPercentage = min($companyBudgetSummary['percentage'], 100);
            $barColor = $companyBudgetSummary['percentage'] > 100 ? 'bg-red-500' : 'bg-teal-500';
        @endphp
        <div class="relative">
            <div class="overflow-hidden h-3 rounded-full bg-gray-200">
                <div class="h-3 rounded-full {{ $barColor }} transition-all duration-500" style="width: {{ number_format($barPercentage, 1) }}%"></div>
            </div>
            <div class="flex justify-between text-sm text-gray-600 mt-2">
                <span>{{ number_format($companyBudgetSummary['total_spent'], 0, ',', '.') }} € genutzt</span>
                <span class="font-medium">{{ number_format($companyBudgetSummary['total_budget'], 0, ',', '.') }} € Budget</span>
            </div>
        </div>
    </div>

    {{-- Team & Service Development --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-purple-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Team & Service Development</h3>
                <p class="text-sm text-gray-500 mt-1">Teamziele + Interne Schulungen + Sonstiges</p>
            </div>
            @if($serviceDevelopmentSummary['planned_budget'] > 0)
                <div class="text-right">
                    <span class="text-sm bg-purple-100 text-purple-800 px-3 py-1 rounded-full font-semibold">
                        Planbudget: {{ number_format($serviceDevelopmentSummary['planned_budget'], 0, ',', '.') }} €
                    </span>
                </div>
            @else
                <div class="text-right">
                    <span class="text-xs bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full">
                        Planbudget noch nicht festgelegt
                    </span>
                </div>
            @endif
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                <p class="text-2xl font-semibold text-purple-900">{{ number_format($serviceDevelopmentSummary['team_goals'], 0, ',', '.') }} €</p>
                <p class="text-sm text-purple-600">Teamziele</p>
            </div>
            <div class="bg-teal-50 rounded-lg p-4 border border-teal-100">
                <p class="text-2xl font-semibold text-teal-900">{{ number_format($serviceDevelopmentSummary['internal_training'], 0, ',', '.') }} €</p>
                <p class="text-sm text-teal-600">Interne Schulungen</p>
            </div>
            <div class="bg-amber-50 rounded-lg p-4 border border-amber-100">
                <p class="text-2xl font-semibold text-amber-900">{{ number_format($serviceDevelopmentSummary['other'], 0, ',', '.') }} €</p>
                <p class="text-sm text-amber-600">Sonstiges</p>
            </div>
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                <p class="text-2xl font-bold text-gray-900">{{ number_format($serviceDevelopmentSummary['total_spent'], 0, ',', '.') }} €</p>
                <p class="text-sm text-gray-600">Gesamt ausgegeben</p>
            </div>
        </div>

        @if($serviceDevelopmentSummary['planned_budget'] > 0)
            @php
                $sdBarPercentage = min($serviceDevelopmentSummary['percentage'], 100);
                $sdBarColor = $serviceDevelopmentSummary['percentage'] > 100 ? 'bg-red-500' : 'bg-purple-500';
            @endphp
            <div class="relative">
                <div class="overflow-hidden h-2 rounded-full bg-gray-200">
                    <div class="h-2 rounded-full {{ $sdBarColor }} transition-all duration-500" style="width: {{ number_format($sdBarPercentage, 1) }}%"></div>
                </div>
                <div class="flex justify-between text-sm text-gray-600 mt-2">
                    <span>{{ number_format($serviceDevelopmentSummary['total_spent'], 0, ',', '.') }} € genutzt</span>
                    <span class="font-medium">{{ number_format($serviceDevelopmentSummary['planned_budget'], 0, ',', '.') }} € geplant</span>
                </div>
            </div>
        @endif
    </div>

    {{-- Team-Karten (klickbar zur Team-Budget-Übersicht) --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white">
            <h2 class="font-semibold text-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Teams ({{ $teams->count() }})
            </h2>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($teamSummaries as $teamData)
                    @php
                        $wbAmpel = $teamData['weiterbildung']['ampel'];
                        $ampelColor = match($wbAmpel) {
                            'green' => 'bg-green-500',
                            'yellow' => 'bg-yellow-500',
                            'red' => 'bg-red-500',
                            default => 'bg-gray-400',
                        };
                    @endphp
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
                        
                        {{-- Weiterbildung --}}
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
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600">Team & Service Dev</span>
                                @if($teamData['service_dev']['budget'] > 0)
                                    <span class="font-semibold {{ $teamData['service_dev']['percentage'] > 100 ? 'text-red-600' : ($teamData['service_dev']['percentage'] >= 75 ? 'text-purple-600' : 'text-gray-600') }}">
                                        {{ $teamData['service_dev']['percentage'] }}%
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </div>
                            @if($teamData['service_dev']['budget'] > 0)
                                <div class="overflow-hidden h-1.5 rounded-full bg-gray-200">
                                    <div class="h-1.5 rounded-full bg-purple-500 transition-all duration-500" 
                                         style="width: {{ min($teamData['service_dev']['percentage'], 100) }}%"></div>
                                </div>
                                <div class="text-[10px] text-gray-400 mt-0.5">
                                    {{ number_format($teamData['service_dev']['spent'], 0, ',', '.') }} € / {{ number_format($teamData['service_dev']['budget'], 0, ',', '.') }} €
                                </div>
                            @else
                                <div class="text-[10px] text-gray-400">
                                    {{ number_format($teamData['service_dev']['spent'], 0, ',', '.') }} € (kein Budget definiert)
                                </div>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Legende --}}
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
