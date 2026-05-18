<div class="card-tool">
    <div class="card-tool-header">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <h2 class="font-semibold text-brand-dark">Sollstunden pro Mitarbeiter ({{ $year }})</h2>
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="setAllToDefault(62)" wire:confirm="Alle sichtbaren Mitarbeiter auf 62h setzen?" class="btn-secondary btn-sm">
                Alle auf 62h
            </button>
            <button wire:click="saveTargetHours" wire:loading.attr="disabled" class="btn-primary btn-sm">
                <span wire:loading.remove wire:target="saveTargetHours">
                    <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Speichern
                </span>
                <span wire:loading wire:target="saveTargetHours">
                    Speichern...
                </span>
            </button>
        </div>
    </div>
    
    <div class="card-tool-body">
        @if($showSuccess)
        <div class="bg-ui-success/10 border border-ui-success/30 text-ui-success rounded-lg px-4 py-3 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            Sollstunden wurden erfolgreich gespeichert!
        </div>
        @endif

        {{-- Stats --}}
        <div class="grid grid-cols-4 gap-4 mb-4">
            <div class="text-center p-3 bg-surface-50 rounded-lg">
                <div class="text-xl font-bold text-brand-dark">{{ $stats['total'] }}</div>
                <div class="text-xs text-surface-500">Mitarbeiter</div>
            </div>
            <div class="text-center p-3 bg-ui-success/10 rounded-lg">
                <div class="text-xl font-bold text-ui-success">{{ $stats['with_hours'] }}</div>
                <div class="text-xs text-surface-500">Mit Sollstunden</div>
            </div>
            <div class="text-center p-3 bg-ui-warning/10 rounded-lg">
                <div class="text-xl font-bold text-ui-warning">{{ $stats['without_hours'] }}</div>
                <div class="text-xs text-surface-500">Ohne Sollstunden</div>
            </div>
            <div class="text-center p-3 bg-brand-primary/10 rounded-lg">
                <div class="text-xl font-bold text-brand-primary">{{ number_format($stats['total_hours'], 0, ',', '.') }}h</div>
                <div class="text-xs text-surface-500">Gesamt Sollstunden</div>
            </div>
        </div>

        {{-- Search --}}
        <div class="mb-4">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search"
                placeholder="Mitarbeiter suchen..."
                class="w-full px-3 py-2 border border-surface-200 rounded-lg focus:border-brand-primary focus:ring-1 focus:ring-brand-primary"
            >
        </div>

        {{-- User List --}}
        <div class="space-y-2 max-h-96 overflow-y-auto">
            @foreach($users as $user)
            <div class="flex items-center gap-3 bg-surface-50 rounded-lg px-3 py-2 hover:bg-surface-100 transition-colors">
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-brand-dark truncate">
                        {{ $user->name }}
                    </div>
                    <div class="text-xs text-surface-400">
                        {{ $user->team?->name ?? 'Kein Team' }} · {{ $user->email }}
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <input 
                        type="text" 
                        wire:model.blur="targetHours.{{ $user->id }}"
                        placeholder="0"
                        class="w-20 px-2 py-1 text-sm text-right border border-surface-200 rounded focus:border-brand-primary focus:ring-1 focus:ring-brand-primary {{ ($targetHours[$user->id] ?? 0) <= 0 ? 'bg-ui-warning/10 border-ui-warning/30' : '' }}"
                    >
                    <span class="text-xs text-surface-400">h/Jahr</span>
                </div>
            </div>
            @endforeach
        </div>

        @if($users->isEmpty())
        <div class="text-center py-8 text-surface-400">
            <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Keine Mitarbeiter gefunden
        </div>
        @endif
    </div>
</div>
