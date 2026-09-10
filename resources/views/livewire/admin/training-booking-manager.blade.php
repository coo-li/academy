@section('page-title', 'Weiterbildung & Coaching buchen')

<div class="space-y-6">
    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    {{-- Team Filter --}}
    @if($teams->count() > 0)
        <div class="bg-dark-card rounded-xl border border-dark-line p-4">
            <div class="flex items-center gap-3 mb-3">
                <svg class="w-5 h-5 text-dark-tx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="text-sm font-semibold text-dark-tx">Team filtern</span>
            </div>
            <div class="flex flex-wrap gap-2">
                {{-- Alle Teams Button --}}
                <button 
                    wire:click="setTeamFilter(null)"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200 
                           {{ $teamFilter === null 
                              ? 'bg-cyan-500 text-white shadow-md shadow-cyan-500/30' 
                              : 'bg-dark-card-hover text-dark-tx-2 hover:bg-dark-line hover:text-dark-tx' }}"
                >
                    <span>Alle Teams</span>
                    <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full 
                                 {{ $teamFilter === null ? 'bg-white/20 text-white' : 'bg-dark-line text-dark-tx-2' }}">
                        {{ $teams->sum('users_count') }}
                    </span>
                </button>

                {{-- Team Buttons --}}
                @foreach($teams as $team)
                    <button 
                        wire:click="setTeamFilter({{ $team->id }})"
                        class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200
                               {{ $teamFilter === $team->id 
                                  ? 'bg-cyan-500 text-white shadow-md shadow-cyan-500/30' 
                                  : 'bg-dark-card-hover text-dark-tx-2 hover:bg-dark-line hover:text-dark-tx' }}"
                    >
                        <span>{{ $team->name }}</span>
                        <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold rounded-full 
                                     {{ $teamFilter === $team->id ? 'bg-white/20 text-white' : 'bg-dark-line text-dark-tx-2' }}">
                            {{ $team->users_count }}
                        </span>
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Tabs --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <button wire:click="setTab('training')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'training' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                        Weiterbildung buchen
                    </div>
                </button>
                <button wire:click="setTab('coaching')"
                        class="px-6 py-4 text-sm font-medium border-b-2 transition-colors {{ $activeTab === 'coaching' ? 'border-primary-500 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                        Coaching buchen
                    </div>
                </button>
                {{-- Link to Coaching Overview --}}
                <a href="{{ route('admin.coaching-overview') }}"
                   class="px-6 py-4 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors ml-auto">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Coach-Übersicht
                    </div>
                </a>
            </nav>
        </div>

        <div class="p-6">
            {{-- Training Tab --}}
            @if($activeTab === 'training')
                {{-- Neue Buchung Button --}}
                @if(!$showTrainingForm)
                    <button wire:click="openTrainingForm"
                            class="mb-6 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Neue Weiterbildung buchen
                    </button>
                @endif

                {{-- Training Form --}}
                @if($showTrainingForm)
                    <div class="mb-6 bg-dark-card rounded-xl p-6 border border-dark-line">
                        <h3 class="text-lg font-semibold text-dark-tx mb-6">
                            {{ $editingTrainingId ? 'Buchung bearbeiten' : 'Neue Weiterbildung buchen' }}
                        </h3>

                        {{-- Budget Warning --}}
                        @if($budgetExceeded)
                            <div class="mb-6 bg-red-900/30 border-2 border-red-500/50 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-semibold text-red-300">Weiterbildungsbudget wird überschritten!</h4>
                                        <p class="text-sm text-red-400 mt-1">Diese Buchung überschreitet das verfügbare Budget des Mitarbeiters.</p>
                                        <label class="flex items-center gap-2 mt-3 cursor-pointer">
                                            <input type="checkbox" wire:model="trainingClevelApproved" class="w-5 h-5 rounded border-red-500 bg-dark-card-hover text-red-500 focus:ring-red-500">
                                            <span class="text-sm font-medium text-red-300">C-Level Genehmigung erteilt</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            {{-- Mitarbeiter --}}
                            <div>
                                <label class="block text-sm font-medium text-dark-tx-2 mb-2">Mitarbeiter *</label>
                                <select wire:model.live="selectedUserId" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                    <option value="">Bitte wählen...</option>
                                    @foreach($availableUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedUserId') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            {{-- Name der Weiterbildung --}}
                            <div class="lg:col-span-2">
                                <label class="block text-sm font-medium text-dark-tx-2 mb-2">Name der Weiterbildung *</label>
                                <input type="text" wire:model="trainingName" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" placeholder="z.B. Google Analytics Conference">
                                @error('trainingName') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                            </div>

                            {{-- Datum --}}
                            <div>
                                <label class="block text-sm font-medium text-dark-tx-2 mb-2">Datum</label>
                                <input type="date" wire:model="trainingDate" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="block text-sm font-medium text-dark-tx-2 mb-2">Status</label>
                                <select wire:model="trainingStatus" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500">
                                    @foreach($statusOptions as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Website --}}
                            <div>
                                <label class="block text-sm font-medium text-dark-tx-2 mb-2">Website/Link</label>
                                <input type="url" wire:model="trainingWebsite" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" placeholder="https://...">
                            </div>
                        </div>

                        {{-- Kosten Sektion --}}
                        <div class="mt-8 pt-6 border-t border-dark-line">
                            <h4 class="text-sm font-semibold text-dark-tx mb-4">Kosten</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-dark-tx-2 mb-2">Kurskosten (Netto)</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="trainingNetCost" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 pr-10" placeholder="0,00">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-dark-tx-2">€</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-tx-2 mb-2">Kurskosten (Brutto)</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" wire:model="trainingCostGross" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 pr-10" placeholder="0,00">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-dark-tx-2">€</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-tx-2 mb-2">Reisekosten</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="trainingTravelCosts" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 pr-10" placeholder="0,00">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-dark-tx-2">€</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-tx-2 mb-2">Übernachtung</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="trainingAccommodationCosts" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 pr-10" placeholder="0,00">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-dark-tx-2">€</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-tx-2 mb-2">Sonstige Kosten</label>
                                    <div class="relative">
                                        <input type="number" step="0.01" wire:model.live.debounce.300ms="trainingOtherCosts" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500 pr-10" placeholder="0,00">
                                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-dark-tx-2">€</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Arbeitszeit Sektion --}}
                        <div class="mt-8 pt-6 border-t border-dark-line">
                            <h4 class="text-sm font-semibold text-dark-tx mb-4">Arbeitszeit</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-dark-tx-2 mb-2">Stunden</label>
                                    <input type="number" step="0.5" wire:model="trainingHours" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" placeholder="z.B. 8">
                                </div>
                                <div class="flex items-center h-12 mt-7">
                                    <label class="flex items-center gap-3 cursor-pointer bg-dark-card-hover px-4 py-3 rounded-lg border border-dark-line hover:border-cyan-500/50 transition-colors">
                                        <input type="checkbox" wire:model="trainingDuringWorkHours" class="w-5 h-5 rounded border-dark-line bg-dark-bg text-cyan-500 focus:ring-cyan-500 focus:ring-offset-dark-card">
                                        <span class="text-sm text-dark-tx">Während Arbeitszeit (wird vom Budget abgezogen)</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- Notizen --}}
                        <div class="mt-8 pt-6 border-t border-dark-line">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-medium text-dark-tx-2 mb-2">Bestellnummer</label>
                                    <input type="text" wire:model="trainingOrderNumber" class="w-full h-12 px-4 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" placeholder="Optional">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-dark-tx-2 mb-2">Notizen</label>
                                    <textarea wire:model="trainingNotes" rows="2" class="w-full px-4 py-3 rounded-lg bg-dark-card-hover border border-dark-line text-dark-tx placeholder-dark-tx-2 focus:border-cyan-500 focus:ring-1 focus:ring-cyan-500" placeholder="Optionale Anmerkungen..."></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="mt-8 pt-6 border-t border-dark-line flex items-center gap-4">
                            <button wire:click="saveTraining" 
                                    class="px-6 py-3 bg-cyan-500 text-white font-semibold rounded-lg hover:bg-cyan-400 transition-colors shadow-lg shadow-cyan-500/25 disabled:opacity-50 disabled:cursor-not-allowed"
                                    @if($budgetExceeded && !$trainingClevelApproved) disabled @endif>
                                {{ $editingTrainingId ? 'Speichern' : 'Buchung erstellen' }}
                            </button>
                            <button wire:click="resetForms" class="px-6 py-3 border border-dark-line text-dark-tx-2 font-medium rounded-lg hover:bg-dark-card-hover hover:text-dark-tx transition-colors">
                                Abbrechen
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Training Bookings List --}}
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mitarbeiter</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Weiterbildung</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Datum</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kosten</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($trainingBookings as $booking)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ $booking->user->name ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">gebucht von: {{ $booking->bookedBy->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="text-gray-900">{{ $booking->name }}</div>
                                        @if($booking->website)
                                            <a href="{{ $booking->website }}" target="_blank" class="text-xs text-primary-600 hover:underline">Link</a>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        {{ $booking->booking_date?->format('d.m.Y') ?? $booking->created_at->format('d.m.Y') }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <div class="font-medium text-gray-900">{{ number_format($booking->total_cost, 2, ',', '.') }} €</div>
                                        @if($booking->hours)
                                            <div class="text-xs text-gray-500">+ {{ number_format($booking->hours, 1, ',', '.') }}h</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        @php
                                            $statusColors = [
                                                'pending' => 'bg-gray-100 text-gray-800',
                                                'ordered' => 'bg-blue-100 text-blue-800',
                                                'completed' => 'bg-green-100 text-green-800',
                                                'on_hold' => 'bg-yellow-100 text-yellow-800',
                                                'cancelled' => 'bg-red-100 text-red-800',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$booking->status] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $booking->status_label }}
                                        </span>
                                        @if($booking->clevel_approved)
                                            <span class="ml-1 text-xs text-green-600" title="C-Level genehmigt">✓</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <button wire:click="editTraining({{ $booking->id }})" class="text-primary-600 hover:text-primary-800 text-sm">
                                            Bearbeiten
                                        </button>
                                        @if(auth()->user()->isAdmin())
                                            <button wire:click="deleteTraining({{ $booking->id }})" 
                                                    wire:confirm="Buchung wirklich löschen?"
                                                    class="ml-2 text-red-600 hover:text-red-800 text-sm">
                                                Löschen
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        Keine Buchungen vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Coaching Tab --}}
            @if($activeTab === 'coaching')
                {{-- Neue Coaching Buchung Button --}}
                @if(!$showCoachingForm)
                    <button wire:click="openCoachingForm"
                            class="mb-6 inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Neues Coaching buchen
                    </button>
                @endif

                {{-- Coaching Form --}}
                @if($showCoachingForm)
                    <div class="mb-6 bg-gray-50 rounded-xl p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            {{ $editingCoachingId ? 'Coaching bearbeiten' : 'Neues Coaching buchen' }}
                        </h3>

                        {{-- Budget Warning (nur für Mitarbeitercoaching) --}}
                        @if($budgetExceeded && $coachingCategory === 'employee')
                            <div class="mb-4 bg-red-50 border-2 border-red-300 rounded-lg p-4">
                                <div class="flex items-start gap-3">
                                    <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <h4 class="font-semibold text-red-800">Weiterbildungsbudget wird überschritten!</h4>
                                        <p class="text-sm text-red-700 mt-1">Diese Coaching-Buchung überschreitet das verfügbare Budget des Mitarbeiters.</p>
                                        <label class="flex items-center gap-2 mt-3 cursor-pointer">
                                            <input type="checkbox" wire:model="coachingClevelApproved" class="rounded border-red-300 text-red-600 focus:ring-red-500">
                                            <span class="text-sm font-medium text-red-800">C-Level Genehmigung erteilt</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Coaching-Kategorie Auswahl --}}
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Coaching-Kategorie *</label>
                            <div class="flex gap-4">
                                <label class="flex items-center gap-3 p-4 bg-white rounded-lg border-2 cursor-pointer transition-all {{ $coachingCategory === 'employee' ? 'border-green-500 bg-green-50' : 'border-gray-200 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="coachingCategory" value="employee" class="text-green-600 focus:ring-green-500">
                                    <div>
                                        <div class="font-medium text-gray-900">Mitarbeitercoaching</div>
                                        <div class="text-sm text-gray-500">Wird vom Weiterbildungsbudget abgezogen</div>
                                    </div>
                                </label>
                                <label class="flex items-center gap-3 p-4 bg-white rounded-lg border-2 cursor-pointer transition-all {{ $coachingCategory === 'leadership' ? 'border-purple-500 bg-purple-50' : 'border-gray-200 hover:border-gray-300' }}">
                                    <input type="radio" wire:model.live="coachingCategory" value="leadership" class="text-purple-600 focus:ring-purple-500">
                                    <div>
                                        <div class="font-medium text-gray-900">Leadership Coaching</div>
                                        <div class="text-sm text-gray-500">Wird NICHT vom Budget abgezogen</div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {{-- Mitarbeiter --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mitarbeiter *</label>
                                <select wire:model.live="selectedUserId" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                    <option value="">Bitte wählen...</option>
                                    @foreach($availableUsers as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedUserId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            {{-- Coach Auswahl --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Coach</label>
                                @if($coaches->count() > 0)
                                    <select wire:model="coachingCoachId" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        <option value="">Bitte wählen...</option>
                                        @foreach($coaches as $coach)
                                            <option value="{{ $coach->id }}">{{ $coach->name }} ({{ $coach->slots_per_month }} Slots/Mo.)</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="text" wire:model="coachingCoachName" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="Name des Coaches">
                                @endif
                            </div>

                            {{-- Coaching-Typ (nur für Mitarbeitercoaching) --}}
                            @if($coachingCategory === 'employee')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Coaching-Programm *</label>
                                    <select wire:model.live="coachingType" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                        @foreach($coachingTypeOptions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            {{-- Stunden (nur für Leadership) --}}
                            @if($coachingCategory === 'leadership')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Stunden *</label>
                                    <div class="relative">
                                        <input type="number" step="0.5" min="0.5" wire:model.live.debounce.300ms="coachingHours" 
                                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 pr-8" 
                                               placeholder="z.B. 10">
                                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">h</span>
                                    </div>
                                    @error('coachingHours') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>

                        {{-- Zeitraum (für Leadership) --}}
                        @if($coachingCategory === 'leadership')
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Von Monat</label>
                                    <input type="month" wire:model="coachingStartMonth" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Bis Monat</label>
                                    <input type="month" wire:model="coachingEndMonth" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buchungsdatum</label>
                                    <input type="date" wire:model="coachingDate" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                            </div>
                        @else
                            {{-- Datum (für Mitarbeitercoaching) --}}
                            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Buchungsdatum</label>
                                    <input type="date" wire:model="coachingDate" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                </div>
                            </div>
                        @endif

                        {{-- Notizen --}}
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notizen</label>
                            <textarea wire:model="coachingNotes" rows="2" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500" placeholder="Optionale Anmerkungen..."></textarea>
                        </div>

                        {{-- Kosten Info --}}
                        <div class="mt-4 p-4 rounded-lg border {{ $coachingCategory === 'leadership' ? 'bg-purple-50 border-purple-200' : 'bg-green-50 border-green-200' }}">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium {{ $coachingCategory === 'leadership' ? 'text-purple-800' : 'text-green-800' }}">
                                        @if($coachingCategory === 'leadership')
                                            <strong>Kosten:</strong> {{ number_format($calculatedCoachingCost, 2, ',', '.') }} €
                                            <span class="text-purple-600">({{ $coachingHours ?? 0 }}h × 250 €/h)</span>
                                        @else
                                            <strong>Kosten:</strong> {{ $coachingType === 'full' ? '1.000,00 €' : '750,00 €' }}
                                        @endif
                                    </p>
                                    <p class="text-xs mt-1 {{ $coachingCategory === 'leadership' ? 'text-purple-600' : 'text-green-600' }}">
                                        @if($coachingCategory === 'leadership')
                                            Wird im Dashboard angezeigt, aber NICHT vom Weiterbildungsbudget abgezogen
                                        @else
                                            Wird automatisch vom Weiterbildungsbudget abgezogen
                                        @endif
                                    </p>
                                </div>
                                @if($coachingCategory === 'leadership')
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        Leadership
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Budget-relevant
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Buttons --}}
                        <div class="mt-6 pt-6 border-t border-gray-200 flex items-center gap-3">
                            <button wire:click="saveCoaching" 
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors"
                                    @if($coachingCategory === 'employee' && $budgetExceeded && !$coachingClevelApproved) disabled @endif>
                                {{ $editingCoachingId ? 'Speichern' : 'Coaching buchen' }}
                            </button>
                            <button wire:click="resetForms" class="px-4 py-2 text-gray-600 hover:text-gray-800 transition-colors">
                                Abbrechen
                            </button>
                        </div>
                    </div>
                @endif

                {{-- Coaching Bookings List --}}
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Mitarbeiter</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategorie / Typ</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Coach</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zeitraum</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Kosten</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($coachingBookings as $booking)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ $booking->user->name ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">gebucht von: {{ $booking->bookedBy->name ?? '-' }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $isLeadership = ($booking->coaching_category ?? 'employee') === 'leadership';
                                        @endphp
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $isLeadership ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $isLeadership ? 'Leadership' : 'Mitarbeiter' }}
                                            </span>
                                            @if(!$isLeadership)
                                                <span class="text-xs text-gray-500">{{ $booking->type_label }}</span>
                                            @endif
                                            @if($booking->hours)
                                                <span class="text-xs text-gray-500">{{ number_format($booking->hours, 1, ',', '.') }} Stunden</span>
                                            @endif
                                        </div>
                                        @if($booking->clevel_approved)
                                            <span class="text-xs text-green-600" title="C-Level genehmigt">✓ Genehmigt</span>
                                        @endif
                                        @if(!$booking->deduct_from_budget)
                                            <span class="text-xs text-purple-600" title="Nicht vom Budget abgezogen">📋 Kein Budget-Abzug</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $booking->coach?->name ?? $booking->coach_name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                        @if($booking->start_month || $booking->end_month)
                                            {{ $booking->period_label }}
                                        @else
                                            {{ $booking->booking_date?->format('d.m.Y') ?? $booking->created_at->format('d.m.Y') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right font-medium text-gray-900">
                                        {{ number_format($booking->cost, 2, ',', '.') }} €
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right">
                                        <button wire:click="editCoaching({{ $booking->id }})" class="text-primary-600 hover:text-primary-800 text-sm">
                                            Bearbeiten
                                        </button>
                                        @if(auth()->user()->isAdmin())
                                            <button wire:click="deleteCoaching({{ $booking->id }})" 
                                                    wire:confirm="Coaching-Buchung wirklich löschen?"
                                                    class="ml-2 text-red-600 hover:text-red-800 text-sm">
                                                Löschen
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        Keine Coaching-Buchungen vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
