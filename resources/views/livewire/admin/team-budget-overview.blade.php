@section('page-title', 'Team Budget-Übersicht')

<div class="space-y-6">
    {{-- Header --}}
    <div>
        <div class="flex items-center gap-3 mb-2">
            <a href="{{ route('admin.dashboard.team') }}" class="text-gray-500 hover:text-gray-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <span class="text-sm text-gray-500">Budget-Übersicht für Team</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">{{ $team->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ $teamBudgetSummary['employee_count'] }} Mitarbeiter</p>
    </div>

    {{-- Team Budget Summary --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-primary-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Team-Weiterbildungsbudget</h3>
                <p class="text-sm text-gray-500 mt-1">Jahr {{ $selectedYear }} · Nur Persönliche Ziele + Externe Schulungen</p>
            </div>
            <div>
                <select wire:model.live="selectedYear"
                        class="rounded-lg border-gray-300 text-sm py-2 px-3 focus:border-primary-500 focus:ring-primary-500 font-medium">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-3xl font-bold {{ $teamBudgetSummary['remaining'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($teamBudgetSummary['remaining'], 0, ',', '.') }} €
                </p>
                <p class="text-sm text-gray-500 mt-1">verbleibend</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-2xl font-semibold text-blue-900">{{ number_format($teamBudgetSummary['total_budget'], 0, ',', '.') }} €</p>
                <p class="text-sm text-blue-600">Gesamtbudget</p>
                <p class="text-xs text-blue-500 mt-1">{{ $teamBudgetSummary['employee_count'] }} × 3.000 €</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                <p class="text-2xl font-semibold text-green-900">{{ number_format($teamBudgetSummary['personal_goals_spent'], 0, ',', '.') }} €</p>
                <p class="text-sm text-green-600">Persönliche Ziele</p>
            </div>
            @if(($teamBudgetSummary['training_count'] ?? 0) > 0)
            <div class="bg-cyan-50 rounded-lg p-4 border border-cyan-100">
                <p class="text-2xl font-semibold text-cyan-900">{{ number_format($teamBudgetSummary['training_costs'], 0, ',', '.') }} €</p>
                <p class="text-sm text-cyan-600">Externe Schulungen</p>
                <p class="text-xs text-cyan-500 mt-1">{{ $teamBudgetSummary['training_count'] }} Buchung(en)</p>
            </div>
            @endif
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                <p class="text-2xl font-semibold text-purple-900">{{ number_format($teamBudgetSummary['percentage'], 0) }}%</p>
                <p class="text-sm text-purple-600">Auslastung</p>
            </div>
        </div>

        {{-- Ladebalken --}}
        @php
            $barPercentage = min($teamBudgetSummary['percentage'], 100);
            $barColor = $teamBudgetSummary['percentage'] > 100 ? 'bg-red-500' : 'bg-teal-500';
        @endphp
        <div class="relative">
            <div class="overflow-hidden h-3 rounded-full bg-gray-200">
                <div class="h-3 rounded-full {{ $barColor }} transition-all duration-500" style="width: {{ number_format($barPercentage, 1) }}%"></div>
            </div>
            <div class="flex justify-between text-sm text-gray-600 mt-2">
                <span>{{ number_format($teamBudgetSummary['total_spent'], 0, ',', '.') }} € genutzt</span>
                <span class="font-medium">{{ number_format($teamBudgetSummary['total_budget'], 0, ',', '.') }} € Budget</span>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="border-b border-gray-200 overflow-x-auto">
            <nav class="flex -mb-px min-w-max">
                <button type="button" wire:click="setTab('uebersicht')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'uebersicht' ? 'border-gray-900 text-gray-900 bg-gray-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Jahresübersicht
                </button>
                <button type="button" wire:click="setTab('weiterbildung')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'weiterbildung' ? 'border-primary-500 text-primary-600 bg-primary-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Persönliche Ziele
                    @if($categoryStats['personal_goals']['count'] > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'weiterbildung' ? 'bg-primary-200 text-primary-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $categoryStats['personal_goals']['count'] }}
                        </span>
                    @endif
                </button>
                <button type="button" wire:click="setTab('externe')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'externe' ? 'border-cyan-500 text-cyan-600 bg-cyan-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Externe Schulungen
                    @if($trainingBookings->count() > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'externe' ? 'bg-cyan-200 text-cyan-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $trainingBookings->count() }}
                        </span>
                    @endif
                </button>
                <button type="button" wire:click="setTab('teamziele')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'teamziele' ? 'border-purple-500 text-purple-600 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Teamziele
                    <span class="text-xs text-gray-400 ml-1">(Service)</span>
                    @if($categoryStats['team_goals']['count'] > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'teamziele' ? 'bg-purple-200 text-purple-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $categoryStats['team_goals']['count'] }}
                        </span>
                    @endif
                </button>
                <button type="button" wire:click="setTab('schulungen')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'schulungen' ? 'border-teal-500 text-teal-600 bg-teal-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Interne Schulungen
                    <span class="text-xs text-gray-400 ml-1">(Service)</span>
                    @if($categoryStats['internal_training']['count'] > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'schulungen' ? 'bg-teal-200 text-teal-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $categoryStats['internal_training']['count'] }}
                        </span>
                    @endif
                </button>
                <button type="button" wire:click="setTab('sonstiges')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'sonstiges' ? 'border-amber-500 text-amber-600 bg-amber-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Sonstiges
                    @if($categoryStats['other']['count'] > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'sonstiges' ? 'bg-amber-200 text-amber-800' : 'bg-gray-200 text-gray-700' }}">
                            {{ $categoryStats['other']['count'] }}
                        </span>
                    @endif
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="p-6">
            @if($activeTab === 'uebersicht')
                {{-- Jahresübersicht mit Matrix --}}
                @include('livewire.admin.partials.team-budget-matrix', ['monthlyBreakdown' => $monthlyBreakdown])
            @elseif($activeTab === 'externe')
                {{-- Externe Schulungen --}}
                @if($trainingBookings->isNotEmpty())
                    <div class="mb-4 p-4 bg-cyan-50 border border-cyan-200 rounded-lg">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-cyan-800">Externe Weiterbildungen</p>
                                <p class="text-xs text-cyan-600">Diese Kosten werden vom Weiterbildungsbudget abgezogen.</p>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mitarbeiter</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weiterbildung</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kosten</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($trainingBookings as $booking)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm text-gray-500">{{ $booking->created_at->format('d.m.Y') }}</td>
                                        <td class="px-4 py-3">
                                            <a href="{{ route('admin.employee-budget', $booking->user_id) }}" 
                                               class="text-sm text-gray-500 hover:text-primary-600 hover:underline">
                                                {{ $booking->user->name }}
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900">{{ $booking->name }}</div>
                                            @if($booking->notes)
                                                <div class="text-xs text-gray-500 mt-1">{{ Str::limit($booking->notes, 50) }}</div>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                            {{ number_format($booking->net_cost, 2, ',', '.') }} €
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-sm font-semibold text-gray-700">Summe</td>
                                    <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">
                                        {{ number_format($trainingBookings->sum('net_cost'), 2, ',', '.') }} €
                                    </td>
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
                        <p class="text-sm mt-1">Für {{ $selectedYear }} wurden keine externen Weiterbildungen gebucht.</p>
                    </div>
                @endif
            @else
                @php
                    $entries = match($activeTab) {
                        'weiterbildung' => $entriesByType['personal_goals'],
                        'teamziele' => $entriesByType['team_goals'],
                        'schulungen' => $entriesByType['internal_training'],
                        'sonstiges' => $entriesByType['other'],
                        default => collect(),
                    };
                    $emptyMessage = match($activeTab) {
                        'weiterbildung' => 'Keine persönlichen Ziele',
                        'teamziele' => 'Keine Teamziele',
                        'schulungen' => 'Keine internen Schulungen',
                        'sonstiges' => 'Keine sonstigen Einträge',
                        default => 'Keine Einträge',
                    };
                @endphp
                @include('livewire.admin.partials.team-budget-entries', ['entries' => $entries, 'emptyMessage' => $emptyMessage])
            @endif
        </div>
    </div>

    {{-- Budget-Kategorien (zählen zum Budget) --}}
    <div class="space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500"></span>
            Zählt zum Weiterbildungsbudget
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <button type="button" wire:click="setTab('weiterbildung')"
                    class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                           {{ $activeTab === 'weiterbildung' ? 'bg-primary-100 border-primary-400 ring-2 ring-primary-300' : 'bg-primary-50 border-primary-200 hover:border-primary-400' }}">
                <p class="text-sm text-primary-700 font-medium">Persönliche Ziele</p>
                <p class="text-2xl font-bold text-primary-900">{{ number_format($categoryStats['personal_goals']['total_amount'], 0, ',', '.') }} €</p>
                <p class="text-xs text-primary-600">{{ $categoryStats['personal_goals']['count'] }} Einträge</p>
            </button>
            <button type="button" wire:click="setTab('externe')"
                    class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                           {{ $activeTab === 'externe' ? 'bg-cyan-100 border-cyan-400 ring-2 ring-cyan-300' : 'bg-cyan-50 border-cyan-200 hover:border-cyan-400' }}">
                <p class="text-sm text-cyan-700 font-medium">Externe Schulungen</p>
                <p class="text-2xl font-bold text-cyan-900">{{ number_format($trainingBookings->sum('net_cost'), 0, ',', '.') }} €</p>
                <p class="text-xs text-cyan-600">{{ $trainingBookings->count() }} Buchung(en)</p>
            </button>
        </div>
    </div>

    {{-- Service-Kategorien (zählen NICHT zum Budget) --}}
    <div class="space-y-4">
        <h3 class="text-sm font-semibold text-gray-500 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-gray-400"></span>
            Service / Team Development (kein Budget-Abzug)
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <button type="button" wire:click="setTab('teamziele')"
                    class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                           {{ $activeTab === 'teamziele' ? 'bg-purple-100 border-purple-400 ring-2 ring-purple-300' : 'bg-gray-50 border-gray-200 hover:border-purple-300' }}">
                <p class="text-sm text-purple-700 font-medium">Teamziele</p>
                <p class="text-2xl font-bold text-purple-900">{{ number_format($categoryStats['team_goals']['total_amount'], 0, ',', '.') }} €</p>
                <p class="text-xs text-gray-500">{{ $categoryStats['team_goals']['count'] }} Einträge</p>
            </button>
            <button type="button" wire:click="setTab('schulungen')"
                    class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                           {{ $activeTab === 'schulungen' ? 'bg-teal-100 border-teal-400 ring-2 ring-teal-300' : 'bg-gray-50 border-gray-200 hover:border-teal-300' }}">
                <p class="text-sm text-teal-700 font-medium">Interne Schulungen</p>
                <p class="text-2xl font-bold text-teal-900">{{ number_format($categoryStats['internal_training']['total_amount'], 0, ',', '.') }} €</p>
                <p class="text-xs text-gray-500">{{ $categoryStats['internal_training']['count'] }} Einträge</p>
            </button>
            <button type="button" wire:click="setTab('sonstiges')"
                    class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                           {{ $activeTab === 'sonstiges' ? 'bg-amber-100 border-amber-400 ring-2 ring-amber-300' : 'bg-gray-50 border-gray-200 hover:border-amber-300' }}">
                <p class="text-sm text-amber-700 font-medium">Sonstiges</p>
                <p class="text-2xl font-bold text-amber-900">{{ number_format($categoryStats['other']['total_amount'], 0, ',', '.') }} €</p>
                <p class="text-xs text-gray-500">{{ $categoryStats['other']['count'] }} Einträge</p>
            </button>
        </div>
    </div>
</div>
