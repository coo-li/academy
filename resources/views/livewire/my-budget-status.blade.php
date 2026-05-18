<div class="space-y-6">
    {{-- Header (nur Titel, ohne Filter) --}}
    <div>
        @if($viewingOther)
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('admin.dashboard.team') }}" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <span class="text-sm text-gray-500">Budget-Status von</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $targetUserName }}</h1>
        @else
            <h1 class="text-2xl font-bold text-gray-900">Mein Budget-Status</h1>
        @endif
    </div>

    {{-- Weiterbildungsbudget (3.000€ Topf) - HELLES DESIGN --}}
    <div class="bg-white rounded-xl shadow-sm border-2 border-primary-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xl font-bold text-gray-900">Weiterbildungsbudget</h3>
                <p class="text-sm text-gray-500 mt-1">
                    {{ $selectedPeriodName }} {{ $selectedYear }} · Stundensatz: {{ number_format($weiterbildungData['hourly_rate'], 0, ',', '.') }} €/h
                </p>
            </div>
            <div class="text-right">
                <span class="text-sm bg-primary-100 text-primary-800 px-3 py-1 rounded-full font-semibold">
                    {{ number_format($weiterbildungData['full_year_allowance'], 0, ',', '.') }} € / Jahr
                </span>
                @if($selectedPeriod !== 'year')
                    <p class="text-xs text-gray-500 mt-1">
                        {{ number_format($weiterbildungData['total_allowance'], 0, ',', '.') }} € für Zeitraum
                    </p>
                @endif
            </div>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-3xl font-bold {{ $weiterbildungData['remaining'] < 0 ? 'text-red-600' : 'text-gray-900' }}">
                    {{ number_format($weiterbildungData['remaining'], 0, ',', '.') }} €
                </p>
                <p class="text-sm text-gray-500 mt-1">verbleibend</p>
            </div>
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-2xl font-semibold text-blue-900">{{ number_format($weiterbildungData['geplant_hours'], 1, ',', '.') }} h</p>
                <p class="text-sm text-blue-600">geplant (Soll)</p>
                <p class="text-xs text-blue-500 mt-1">= {{ number_format($weiterbildungData['geplant_euros'], 0, ',', '.') }} €</p>
            </div>
            <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                <p class="text-2xl font-semibold text-green-900">{{ number_format($weiterbildungData['genutzt_hours'], 1, ',', '.') }} h</p>
                <p class="text-sm text-green-600">genutzt (Ist)</p>
                <p class="text-xs text-green-500 mt-1">= {{ number_format($weiterbildungData['genutzt_euros'], 0, ',', '.') }} €</p>
            </div>
            @if(($weiterbildungData['training_count'] ?? 0) > 0)
            <div class="bg-teal-50 rounded-lg p-4 border border-teal-100">
                <p class="text-2xl font-semibold text-teal-900">{{ number_format($weiterbildungData['training_costs_euros'], 0, ',', '.') }} €</p>
                <p class="text-sm text-teal-600">Externe Schulungen</p>
                <p class="text-xs text-teal-500 mt-1">{{ $weiterbildungData['training_count'] }} Buchung(en)</p>
            </div>
            @endif
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-100">
                @php
                    $nutzung = $weiterbildungData['geplant_hours'] > 0 
                        ? ($weiterbildungData['genutzt_hours'] / $weiterbildungData['geplant_hours']) * 100 
                        : 0;
                @endphp
                <p class="text-2xl font-semibold text-purple-900">{{ number_format($nutzung, 0) }}%</p>
                <p class="text-sm text-purple-600">Ist / Soll</p>
            </div>
        </div>

        <div class="relative">
            @php
                $barPercentage = min($weiterbildungData['percentage'], 100);
                $barColor = $weiterbildungData['percentage'] > 100 ? 'bg-red-500' : 'bg-teal-500';
                $totalGeplant = $weiterbildungData['geplant_euros'] + ($weiterbildungData['training_costs_euros'] ?? 0);
            @endphp
            <div class="overflow-hidden h-3 rounded-full bg-gray-200">
                <div style="width: {{ $barPercentage }}%"
                     class="h-3 rounded-full {{ $barColor }} transition-all duration-500"></div>
            </div>
            <div class="flex justify-between text-sm text-gray-600 mt-2">
                <span>
                    {{ number_format($totalGeplant, 0, ',', '.') }} € verplant
                    @if(($weiterbildungData['training_costs_euros'] ?? 0) > 0)
                        <span class="text-xs text-gray-400">(inkl. {{ number_format($weiterbildungData['training_costs_euros'], 0, ',', '.') }} € externe Schulungen)</span>
                    @endif
                </span>
                <span class="font-medium">{{ number_format($weiterbildungData['total_allowance'], 0, ',', '.') }} € Budget</span>
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
                <button type="button" wire:click="setTab('teamziele')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'teamziele' ? 'border-purple-500 text-purple-600 bg-purple-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Teamziele
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
                <button type="button" wire:click="setTab('archiv')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors
                               {{ $activeTab === 'archiv' ? 'border-orange-500 text-orange-600 bg-orange-50' : 'border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
                    Archiv
                    @if($archivedCount > 0)
                        <span class="ml-2 px-2 py-0.5 text-xs rounded-full {{ $activeTab === 'archiv' ? 'bg-orange-200 text-orange-800' : 'bg-gray-200 text-gray-700' }}">
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

    {{-- Kategorie-Übersicht (klickbar!) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <button type="button" wire:click="setTab('weiterbildung')"
                class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                       {{ $activeTab === 'weiterbildung' ? 'bg-primary-100 border-primary-400 ring-2 ring-primary-300' : 'bg-primary-50 border-primary-200 hover:border-primary-400' }}">
            <p class="text-sm text-primary-700 font-medium">Persönliche Ziele</p>
            <p class="text-2xl font-bold text-primary-900">{{ number_format($categoryStats['personal_goals']['ist_hours'], 1, ',', '.') }} h</p>
            <p class="text-xs text-primary-600">
                {{ $categoryStats['personal_goals']['count'] }} Ziele · 
                @if(($categoryStats['personal_goals']['soll_hours'] ?? 0) > 0)
                    {{ number_format($categoryStats['personal_goals']['verwendung'], 0) }}%
                @else
                    -
                @endif
            </p>
        </button>
        <button type="button" wire:click="setTab('teamziele')"
                class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                       {{ $activeTab === 'teamziele' ? 'bg-purple-100 border-purple-400 ring-2 ring-purple-300' : 'bg-purple-50 border-purple-200 hover:border-purple-400' }}">
            <p class="text-sm text-purple-700 font-medium">Teamziele</p>
            <p class="text-2xl font-bold text-purple-900">{{ number_format($categoryStats['team_goals']['ist_hours'], 1, ',', '.') }} h</p>
            <p class="text-xs text-purple-600">
                {{ $categoryStats['team_goals']['count'] }} Ziele · 
                @if(($categoryStats['team_goals']['soll_hours'] ?? 0) > 0)
                    {{ number_format($categoryStats['team_goals']['verwendung'], 0) }}%
                @else
                    -
                @endif
            </p>
        </button>
        <button type="button" wire:click="setTab('schulungen')"
                class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                       {{ $activeTab === 'schulungen' ? 'bg-teal-100 border-teal-400 ring-2 ring-teal-300' : 'bg-teal-50 border-teal-200 hover:border-teal-400' }}">
            <p class="text-sm text-teal-700 font-medium">Interne Schulungen</p>
            <p class="text-2xl font-bold text-teal-900">{{ number_format($categoryStats['internal_training']['ist_hours'], 1, ',', '.') }} h</p>
            <p class="text-xs text-teal-600">
                {{ $categoryStats['internal_training']['count'] }} Ziele · 
                @if(($categoryStats['internal_training']['soll_hours'] ?? 0) > 0)
                    {{ number_format($categoryStats['internal_training']['verwendung'], 0) }}%
                @else
                    -
                @endif
            </p>
        </button>
        <button type="button" wire:click="setTab('sonstiges')"
                class="text-left rounded-lg p-4 transition-all hover:shadow-md border-2
                       {{ $activeTab === 'sonstiges' ? 'bg-amber-100 border-amber-400 ring-2 ring-amber-300' : 'bg-amber-50 border-amber-200 hover:border-amber-400' }}">
            <p class="text-sm text-amber-700 font-medium">Sonstiges</p>
            <p class="text-2xl font-bold text-amber-900">{{ number_format($categoryStats['other']['ist_hours'], 1, ',', '.') }} h</p>
            <p class="text-xs text-amber-600">
                {{ $categoryStats['other']['count'] }} Ziele · 
                @if(($categoryStats['other']['soll_hours'] ?? 0) > 0)
                    {{ number_format($categoryStats['other']['verwendung'], 0) }}%
                @else
                    -
                @endif
            </p>
        </button>
    </div>
</div>
