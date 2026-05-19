@section('page-title', 'Stundensätze pflegen')

<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Stundensätze pflegen</h1>
        <p class="text-surface-500 mt-1.5 text-base">Stundensätze nach Karrierestufe verwalten</p>
    </div>

<div class="card-tool" x-data="{ rates: @js($hourlyRates) }">
    <div class="card-tool-header">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h2 class="font-semibold text-brand-dark">Stundensätze nach Karrierestufe</h2>
        </div>
        <button wire:click="saveRates" wire:loading.attr="disabled" class="btn-primary btn-sm">
            <span wire:loading.remove wire:target="saveRates">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Speichern
            </span>
            <span wire:loading wire:target="saveRates">
                <svg class="animate-spin w-4 h-4 mr-1 inline" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Speichern...
            </span>
        </button>
    </div>
    
    <div class="card-tool-body">
        @if($showSuccess)
        <div class="bg-ui-success/10 border border-ui-success/30 text-ui-success rounded-lg px-4 py-3 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Stundensätze wurden erfolgreich gespeichert!
        </div>
        @endif

        <p class="text-sm text-surface-500 mb-4">
            Die Stundensätze werden für die Budget-Auswertung verwendet, um monetäre Kosten in Stunden umzurechnen.
            Leer lassen = kein Stundensatz definiert (Fallback: 100€/h).
        </p>

        <div class="space-y-6">
            @foreach($groupedLevels as $pathName => $levels)
            <div>
                <h3 class="text-sm font-semibold text-surface-600 uppercase tracking-wide mb-3">
                    {{ $pathName }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
                    @foreach($levels as $level)
                    <div class="flex items-center gap-2 bg-surface-50 rounded-lg px-3 py-2" wire:key="level-{{ $level->id }}">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-brand-dark truncate">
                                {{ $level->title }}
                            </div>
                            <div class="text-xs text-surface-400">
                                Stufe {{ $level->level_number }}
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <input 
                                type="text" 
                                x-model="rates['{{ $level->id }}']"
                                @change="$wire.updateRate('{{ $level->id }}', rates['{{ $level->id }}'])"
                                placeholder="--"
                                class="w-20 px-2 py-1 text-sm text-right border border-surface-200 rounded focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
                            >
                            <span class="text-xs text-surface-400">€/h</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
</div>
