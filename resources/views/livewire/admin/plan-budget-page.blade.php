@section('page-title', 'Planbudgets verwalten')

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Planbudgets verwalten</h1>
            <p class="text-sm text-gray-500 mt-1">Umsatzziele und Service Development Budgets festlegen</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="selectedYear"
                    class="rounded-lg border-gray-300 text-sm py-2 px-3 focus:border-purple-500 focus:ring-purple-500 font-medium">
                @foreach($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Flash Message --}}
    @if(session()->has('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-center gap-2">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Unternehmensweite Einstellungen --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-purple-200 p-6">
        <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            Unternehmensweite Einstellungen ({{ $selectedYear }})
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Umsatzziel --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Umsatzziel {{ $selectedYear }}
                </label>
                <div class="relative">
                    <input type="number" 
                           wire:model.live.debounce.500ms="revenueTarget"
                           class="w-full rounded-lg border-gray-300 pr-10 text-lg font-semibold focus:border-purple-500 focus:ring-purple-500"
                           placeholder="0"
                           min="0"
                           step="1000">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">€</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Geplanter Jahresumsatz des Unternehmens</p>
            </div>

            {{-- Prozentsatz für Service Development --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Service Development Budget (%)
                </label>
                <div class="relative">
                    <input type="number" 
                           wire:model.live.debounce.500ms="serviceDevPercentage"
                           class="w-full rounded-lg border-gray-300 pr-10 text-lg font-semibold focus:border-purple-500 focus:ring-purple-500"
                           placeholder="0"
                           min="0"
                           max="100"
                           step="0.1">
                    <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-medium">%</span>
                </div>
                <p class="text-xs text-gray-500 mt-1">Anteil vom Umsatz für Team & Service Development</p>
            </div>
        </div>

        {{-- Berechnetes Service Dev Budget --}}
        <div class="mt-6 p-4 bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg border border-purple-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-purple-700">Berechnetes Service Development Budget</p>
                    <p class="text-xs text-purple-600 mt-0.5">
                        {{ number_format($revenueTarget, 0, ',', '.') }} € × {{ number_format($serviceDevPercentage, 1, ',', '.') }}%
                    </p>
                </div>
                <p class="text-3xl font-bold text-purple-900">
                    {{ number_format($serviceDevBudget, 0, ',', '.') }} €
                </p>
            </div>
        </div>
    </div>

    {{-- Team-Allokationen --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white flex items-center justify-between">
            <h2 class="font-semibold text-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Team-Budgetverteilung
            </h2>
            <button wire:click="distributeEvenly"
                    class="text-sm bg-white/20 hover:bg-white/30 px-3 py-1.5 rounded-lg transition-colors">
                Gleichmäßig verteilen
            </button>
        </div>

        <div class="p-6">
            {{-- Summen-Anzeige --}}
            <div class="mb-4 p-3 rounded-lg {{ $totalAllocationPercentage > 100 ? 'bg-red-50 border border-red-200' : ($totalAllocationPercentage == 100 ? 'bg-green-50 border border-green-200' : 'bg-yellow-50 border border-yellow-200') }}">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium {{ $totalAllocationPercentage > 100 ? 'text-red-700' : ($totalAllocationPercentage == 100 ? 'text-green-700' : 'text-yellow-700') }}">
                        Gesamtverteilung:
                    </span>
                    <span class="font-bold {{ $totalAllocationPercentage > 100 ? 'text-red-900' : ($totalAllocationPercentage == 100 ? 'text-green-900' : 'text-yellow-900') }}">
                        {{ number_format($totalAllocationPercentage, 1, ',', '.') }}%
                        @if($totalAllocationPercentage != 100)
                            <span class="text-xs font-normal">(sollte 100% sein)</span>
                        @endif
                    </span>
                </div>
            </div>

            {{-- Team-Liste --}}
            <div class="space-y-3">
                @foreach($teamAllocations as $teamId => $data)
                    @php
                        $teamBudget = $teamBudgets[$teamId] ?? ['amount' => 0];
                    @endphp
                    <div class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                        <div class="flex-1">
                            <p class="font-medium text-gray-900">{{ $data['team_name'] }}</p>
                        </div>
                        <div class="w-32">
                            <div class="relative">
                                <input type="number" 
                                       wire:model.live.debounce.300ms="teamAllocations.{{ $teamId }}.percentage"
                                       class="w-full rounded-lg border-gray-300 pr-8 text-right text-sm font-semibold focus:border-purple-500 focus:ring-purple-500"
                                       placeholder="0"
                                       min="0"
                                       max="100"
                                       step="0.5">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">%</span>
                            </div>
                        </div>
                        <div class="w-40 text-right">
                            <p class="text-lg font-bold text-purple-700">
                                {{ number_format($teamBudget['amount'], 0, ',', '.') }} €
                            </p>
                            <p class="text-xs text-gray-500">Team-Budget</p>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Gesamt-Summe --}}
            <div class="mt-6 p-4 bg-purple-50 rounded-lg border border-purple-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-purple-800">Summe aller Team-Budgets:</span>
                    <span class="text-xl font-bold text-purple-900">
                        {{ number_format(collect($teamBudgets)->sum('amount'), 0, ',', '.') }} €
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Speichern-Button --}}
    <div class="flex justify-end gap-3">
        <a href="{{ route('admin.dashboard.budgets') }}" 
           class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            Zurück zur Übersicht
        </a>
        <button wire:click="save"
                class="px-6 py-3 text-sm font-medium text-white bg-purple-600 rounded-lg hover:bg-purple-700 transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Budgets speichern
        </button>
    </div>

    {{-- Info-Box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm text-blue-800">
                <p class="font-medium mb-1">So funktioniert die Budgetplanung:</p>
                <ul class="list-disc list-inside space-y-1 text-blue-700">
                    <li><strong>Umsatzziel</strong>: Geplanter Jahresumsatz des Unternehmens</li>
                    <li><strong>Service Dev %</strong>: Welcher Anteil vom Umsatz für Team & Service Development verwendet werden soll</li>
                    <li><strong>Team-Verteilung</strong>: Wie das Service Dev Budget auf die Teams aufgeteilt wird (sollte 100% ergeben)</li>
                    <li>Das <strong>Weiterbildungsbudget</strong> (3.000€ pro MA) läuft separat und wird automatisch berechnet</li>
                </ul>
            </div>
        </div>
    </div>
</div>
