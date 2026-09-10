<div>
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Coaching-Übersicht {{ $year }}</h1>
        <p class="text-gray-600">Slot-basierte Monatsübersicht aller Coaches</p>
    </div>

    {{-- Navigation und Filter --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        {{-- Jahr-Navigation --}}
        <div class="flex items-center gap-2">
            <button 
                wire:click="previousYear"
                class="px-3 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <span class="px-4 py-2 font-semibold text-gray-900 bg-white border border-gray-300 rounded-lg">
                {{ $year }}
            </span>
            <button 
                wire:click="nextYear"
                class="px-3 py-2 text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>

        {{-- Kategorie-Filter --}}
        <div class="flex gap-2">
            <button 
                wire:click="$set('categoryFilter', 'all')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $categoryFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
            >
                Alle
            </button>
            <button 
                wire:click="$set('categoryFilter', 'employee')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $categoryFilter === 'employee' ? 'bg-green-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
            >
                Mitarbeiter
            </button>
            <button 
                wire:click="$set('categoryFilter', 'leadership')"
                class="px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $categoryFilter === 'leadership' ? 'bg-purple-600 text-white' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' }}"
            >
                Leadership
            </button>
        </div>
    </div>

    {{-- Legende --}}
    <div class="flex gap-4 mb-4 text-sm">
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 bg-green-500 rounded"></span>
            <span class="text-gray-600">Mitarbeitercoaching</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 bg-purple-500 rounded"></span>
            <span class="text-gray-600">Leadership Coaching</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-4 h-4 bg-gray-200 rounded border border-gray-300"></span>
            <span class="text-gray-600">Freier Slot</span>
        </div>
    </div>

    {{-- Übersichts-Tabelle --}}
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky left-0 bg-gray-50 z-10 min-w-[150px]">
                        Coach
                    </th>
                    @foreach($months as $monthNum => $monthName)
                        <th class="px-3 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[100px] {{ $monthNum === now()->month && $year === now()->year ? 'bg-blue-50' : '' }}">
                            {{ $monthName }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($this->coaches as $coach)
                    <tr class="hover:bg-gray-50">
                        {{-- Coach Name --}}
                        <td class="px-4 py-3 whitespace-nowrap sticky left-0 bg-white z-10 border-r border-gray-200">
                            <div class="font-medium text-gray-900">{{ $coach->name }}</div>
                            <div class="text-xs text-gray-500">{{ $coach->slots_per_month }} Slots/Monat</div>
                        </td>

                        {{-- Monate --}}
                        @foreach($months as $monthNum => $monthName)
                            @php
                                $slots = $this->getSlotData($coach->id, $monthNum);
                                $summary = $this->getMonthSummary($coach->id, $monthNum);
                                $isCurrentMonth = $monthNum === now()->month && $year === now()->year;
                            @endphp
                            <td class="px-2 py-2 {{ $isCurrentMonth ? 'bg-blue-50' : '' }}">
                                <div class="flex flex-col gap-1">
                                    @foreach($slots as $slot)
                                        @if($slot['filled'])
                                            <div 
                                                class="px-2 py-1 text-xs rounded truncate cursor-pointer {{ $slot['category'] === 'leadership' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}"
                                                title="{{ $slot['user_name'] }} - {{ $slot['category'] === 'leadership' ? 'Leadership' : ($slot['type'] === 'full' ? 'Volles Programm' : 'Quick Help') }}"
                                            >
                                                {{ Str::limit($slot['user_name'], 10) }}
                                            </div>
                                        @else
                                            <div class="px-2 py-1 text-xs text-gray-400 bg-gray-100 rounded text-center border border-dashed border-gray-300">
                                                —
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($months) + 1 }}" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                                <p class="font-medium">Keine Coaches vorhanden</p>
                                <p class="text-sm">Bitte fügen Sie zuerst Coaches hinzu.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Zusammenfassung --}}
    @if($this->coaches->isNotEmpty())
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Gesamtbuchungen --}}
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Buchungen {{ $year }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $this->bookings->count() }}</div>
                <div class="mt-1 text-sm text-gray-500">
                    {{ $this->bookings->where('coaching_category', 'employee')->count() }} Mitarbeiter,
                    {{ $this->bookings->where('coaching_category', 'leadership')->count() }} Leadership
                </div>
            </div>

            {{-- Aktive Coaches --}}
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Aktive Coaches</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $this->coaches->count() }}</div>
                <div class="mt-1 text-sm text-gray-500">
                    {{ $this->coaches->sum('slots_per_month') }} Slots/Monat gesamt
                </div>
            </div>

            {{-- Auslastung aktueller Monat --}}
            @php
                $currentMonthBookings = $this->bookings->filter(fn($b) => $b->booking_date->month === now()->month)->count();
                $totalSlotsThisMonth = $this->coaches->sum('slots_per_month');
                $utilizationPercent = $totalSlotsThisMonth > 0 ? round(($currentMonthBookings / $totalSlotsThisMonth) * 100) : 0;
            @endphp
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Auslastung {{ now()->translatedFormat('F') }}</div>
                <div class="mt-1 text-2xl font-bold {{ $utilizationPercent >= 80 ? 'text-green-600' : ($utilizationPercent >= 50 ? 'text-yellow-600' : 'text-gray-900') }}">
                    {{ $utilizationPercent }}%
                </div>
                <div class="mt-1 text-sm text-gray-500">
                    {{ $currentMonthBookings }} von {{ $totalSlotsThisMonth }} Slots belegt
                </div>
            </div>
        </div>
    @endif
</div>
