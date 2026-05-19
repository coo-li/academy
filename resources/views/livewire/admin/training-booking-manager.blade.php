<div class="space-y-8">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Weiterbildung einbuchen</h1>
        <p class="mt-2 text-sm text-gray-500">
            Buche eine externe Weiterbildung für einen Mitarbeiter ein. Es wird automatisch eine Asana-Task erstellt, 
            die dich daran erinnert, den Budget-Eintrag anzulegen.
        </p>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg bg-green-50 border border-green-200 p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex items-center">
                <svg class="h-5 w-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    {{-- Buchungsformular --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="px-6 py-5 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900">Neue Buchung erstellen</h2>
        </div>
        
        <form wire:submit="createBooking" class="p-6 space-y-8">
            {{-- Sektion 1: Mitarbeiter-Auswahl --}}
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Mitarbeiter</h3>
                
                {{-- Team-Filter (nur wenn mehrere Teams) --}}
                @if($teams->count() > 1)
                    <div class="flex flex-wrap gap-2">
                        <button type="button" 
                                wire:click="$set('selectedTeamId', null)"
                                class="px-4 py-2 text-sm font-medium rounded-lg transition-all border-2
                                       {{ $selectedTeamId === null 
                                          ? 'bg-cyan-100 border-cyan-500 text-cyan-800 ring-2 ring-cyan-200 shadow-sm' 
                                          : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 hover:border-gray-300' }}">
                            Alle{{ $isAdmin ? '' : ' meine' }}
                        </button>
                        @foreach($teams as $team)
                            <button type="button" 
                                    wire:click="$set('selectedTeamId', {{ $team->id }})"
                                    class="px-4 py-2 text-sm font-medium rounded-lg transition-all border-2
                                           {{ $selectedTeamId === $team->id 
                                              ? 'bg-cyan-100 border-cyan-500 text-cyan-800 ring-2 ring-cyan-200 shadow-sm' 
                                              : 'bg-gray-50 border-gray-200 text-gray-700 hover:bg-gray-100 hover:border-gray-300' }}">
                                {{ $team->name }}
                            </button>
                        @endforeach
                    </div>
                @endif
                
                {{-- Mitarbeiter-Dropdown --}}
                <div>
                    <label for="userId" class="block text-sm font-medium text-gray-700 mb-2">
                        Mitarbeiter auswählen <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="userId" id="userId" 
                            class="w-full rounded-lg border-2 border-gray-300 bg-gray-50 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:ring-2 focus:bg-white text-base py-3 pl-5 pr-10">
                        <option value="">-- Bitte auswählen --</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    @error('userId')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @if($employees->isEmpty())
                        <p class="mt-2 text-sm text-gray-500">
                            @if($selectedTeamId)
                                Keine Mitarbeitenden in diesem Team gefunden.
                            @else
                                Keine Mitarbeitenden gefunden.
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            <hr class="border-gray-200">

            {{-- Sektion 2: Weiterbildungs-Details --}}
            <div class="space-y-6">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Weiterbildungs-Details</h3>
                
                {{-- Name der Weiterbildung --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Name der Weiterbildung <span class="text-red-500">*</span>
                    </label>
                    <input type="text" wire:model="name" id="name" 
                           class="w-full rounded-lg border-2 border-gray-300 bg-gray-50 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:ring-2 focus:bg-white text-base py-3 pl-5 pr-4"
                           placeholder="z.B. Google Analytics 4 Zertifizierung">
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Kosten --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="netCost" class="block text-sm font-medium text-gray-700 mb-2">
                            Nettokosten <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" wire:model="netCost" id="netCost" step="0.01" min="0"
                                   class="w-full rounded-lg border-2 border-gray-300 bg-gray-50 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:ring-2 focus:bg-white text-base py-3 pl-5 pr-12"
                                   placeholder="0,00">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">€</span>
                        </div>
                        @error('netCost')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-end">
                        <label class="flex items-center gap-3 cursor-pointer p-3 rounded-lg border-2 border-gray-200 hover:bg-gray-50 hover:border-gray-300 transition-colors">
                            <input type="checkbox" wire:model="requiresGrossBilling" 
                                   class="w-5 h-5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                            <span class="text-sm text-gray-700">Muss brutto abgerechnet werden</span>
                        </label>
                    </div>
                </div>
            </div>

            <hr class="border-gray-200">

            {{-- Sektion 3: Arbeitszeit --}}
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Arbeitszeit</h3>
                
                <div class="bg-blue-50 rounded-xl p-5 border-2 border-blue-200">
                    <label class="flex items-start gap-4 cursor-pointer">
                        <input type="checkbox" wire:model.live="duringWorkHours" 
                               class="w-5 h-5 mt-0.5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                        <div>
                            <span class="text-sm font-medium text-gray-900">Wird in Arbeitszeit gemacht</span>
                            <p class="text-sm text-gray-500 mt-1">
                                Die Weiterbildung findet während der regulären Arbeitszeit statt und die Stunden werden als Weiterbildungszeit erfasst.
                            </p>
                        </div>
                    </label>

                    @if($duringWorkHours)
                        <div class="mt-5 pl-9">
                            <label for="hours" class="block text-sm font-medium text-gray-700 mb-2">
                                Geplante Stunden <span class="text-red-500">*</span>
                            </label>
                            <div class="relative w-40">
                                <input type="number" wire:model="hours" id="hours" step="0.5" min="0"
                                       class="w-full rounded-lg border-2 border-gray-300 bg-white shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:ring-2 text-base py-3 pl-5 pr-10"
                                       placeholder="0">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">h</span>
                            </div>
                            @error('hours')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif
                </div>
            </div>

            <hr class="border-gray-200">

            {{-- Sektion 4: Optionale Notizen --}}
            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Zusätzliche Informationen (optional)</h3>
                
                <div>
                    <textarea wire:model="notes" id="notes" rows="3"
                              class="w-full rounded-lg border-2 border-gray-300 bg-gray-50 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 focus:ring-2 focus:bg-white text-base py-3 pl-5 pr-4"
                              placeholder="z.B. Anbieter, Link zur Schulung, besondere Hinweise..."></textarea>
                </div>
            </div>

            {{-- Submit --}}
            <div class="pt-4">
                <button type="submit" 
                        class="w-full sm:w-auto px-8 py-3 bg-cyan-600 text-white font-semibold rounded-lg shadow-sm hover:bg-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 transition-colors text-base"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-wait">
                    <span wire:loading.remove wire:target="createBooking" class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Buchung erstellen
                    </span>
                    <span wire:loading wire:target="createBooking" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Wird erstellt...
                    </span>
                </button>
            </div>
        </form>
    </div>

    {{-- Offene Buchungen --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 bg-amber-50">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-100 rounded-lg">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Offene Buchungen</h2>
                    <p class="text-sm text-gray-600 mt-0.5">
                        Für diese Buchungen muss noch ein Budget-Eintrag angelegt werden. 
                        Sobald du das erledigt hast, klicke auf "Erledigt" - die Asana-Task wird dann geschlossen.
                    </p>
                </div>
            </div>
        </div>

        @if($pendingBookings->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Datum</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mitarbeiter</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Weiterbildung</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Kosten</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Arbeitszeit</th>
                            <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Aktion</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pendingBookings as $booking)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                    {{ $booking->created_at->format('d.m.Y') }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $booking->user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $booking->user->team?->name ?? '–' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $booking->name }}</div>
                                    @if($booking->requires_gross_billing)
                                        <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Brutto-Abrechnung
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 whitespace-nowrap">
                                    {{ number_format($booking->net_cost, 2, ',', '.') }} €
                                </td>
                                <td class="px-6 py-4 text-center whitespace-nowrap">
                                    @if($booking->during_work_hours)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                            {{ number_format($booking->hours, 1, ',', '.') }} h
                                        </span>
                                    @else
                                        <span class="text-gray-400">–</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button type="button" 
                                            wire:click="markComplete({{ $booking->id }})"
                                            wire:confirm="Hast du den Budget-Eintrag angelegt? Die Buchung wird als erledigt markiert und die Asana-Task geschlossen."
                                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-green-700 bg-green-100 rounded-lg hover:bg-green-200 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Erledigt
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-16 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-lg font-medium text-gray-900">Keine offenen Buchungen</p>
                <p class="text-sm text-gray-500 mt-1">Alle Budget-Einträge wurden angelegt.</p>
            </div>
        @endif
    </div>

    {{-- Erledigte Buchungen (Übersicht) --}}
    @if($completedBookings->isNotEmpty())
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-200 bg-green-50">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-green-100 rounded-lg">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Erledigte Buchungen</h2>
                    <p class="text-sm text-gray-600 mt-0.5">
                        Letzte 20 Buchungen, für die ein Budget-Eintrag angelegt wurde.
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Datum</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Mitarbeiter</th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Weiterbildung</th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Kosten</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Arbeitszeit</th>
                        <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($completedBookings as $booking)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $booking->created_at->format('d.m.Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $booking->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $booking->user->team?->name ?? '–' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $booking->name }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-semibold text-gray-900 whitespace-nowrap">
                                {{ number_format($booking->net_cost, 2, ',', '.') }} €
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if($booking->during_work_hours)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                        {{ number_format($booking->hours, 1, ',', '.') }} h
                                    </span>
                                @else
                                    <span class="text-gray-400">–</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Erledigt
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
