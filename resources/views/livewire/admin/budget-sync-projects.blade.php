@section('page-title', 'Budget-Sync Projekte')

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Budget-Sync Projekte</h1>
            <p class="text-sm text-gray-500 mt-1">
                Konfigurieren Sie welche Projekte aus dem Budget-Tracker synchronisiert werden
            </p>
        </div>
        <div class="flex gap-2">
            <button wire:click="importFromExistingEntries"
                    wire:confirm="Projekte aus bestehenden Budget-Einträgen importieren?"
                    class="btn-secondary text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Aus Bestand importieren
            </button>
            <button wire:click="$toggle('showAddForm')" class="btn-primary text-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Projekt hinzufügen
            </button>
        </div>
    </div>

    {{-- Flash Message --}}
    @if($message)
        <div class="rounded-lg p-4 {{ $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' }}">
            <div class="flex justify-between items-center">
                <span>{{ $message }}</span>
                <button wire:click="dismissMessage" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    {{-- Statistiken --}}
    <div class="grid grid-cols-3 gap-4">
        <button wire:click="$set('statusFilter', 'all')" 
                class="text-left bg-white rounded-lg p-4 border-2 transition-colors {{ $statusFilter === 'all' ? 'border-gray-900 bg-gray-50' : 'border-gray-200 hover:border-gray-400' }} shadow-sm">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-sm text-gray-500">Gesamt</p>
        </button>
        <button wire:click="$set('statusFilter', 'active')" 
                class="text-left bg-green-50 rounded-lg p-4 border-2 transition-colors {{ $statusFilter === 'active' ? 'border-green-500 ring-2 ring-green-200' : 'border-green-200 hover:border-green-400' }} shadow-sm">
            <p class="text-2xl font-bold text-green-800">{{ $stats['active'] }}</p>
            <p class="text-sm text-green-600">Aktiv</p>
        </button>
        <button wire:click="$set('statusFilter', 'inactive')" 
                class="text-left bg-gray-100 rounded-lg p-4 border-2 transition-colors {{ $statusFilter === 'inactive' ? 'border-gray-500 ring-2 ring-gray-300' : 'border-gray-300 hover:border-gray-400' }} shadow-sm">
            <p class="text-2xl font-bold text-gray-700">{{ $stats['inactive'] }}</p>
            <p class="text-sm text-gray-500">Inaktiv</p>
        </button>
    </div>

    {{-- Formular für neues Projekt --}}
    @if($showAddForm)
        <div class="bg-white rounded-lg p-6 border border-gray-200 shadow-sm">
            <h3 class="font-semibold text-gray-900 mb-4">Neues Projekt hinzufügen</h3>
            <form wire:submit="addProject" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Projekt-ID *</label>
                        <input type="text" wire:model="newProjectId" 
                               class="w-full rounded-lg border-gray-300 text-sm"
                               placeholder="z.B. 12345">
                        @error('newProjectId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Projektname *</label>
                        <input type="text" wire:model="newProjectName" 
                               class="w-full rounded-lg border-gray-300 text-sm"
                               placeholder="z.B. Teamziele Creation">
                        @error('newProjectName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Kunde</label>
                        <input type="text" wire:model="newCustomerName" 
                               class="w-full rounded-lg border-gray-300 text-sm"
                               placeholder="z.B. trafficdesign">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary text-sm">Hinzufügen</button>
                    <button type="button" wire:click="$set('showAddForm', false)" class="btn-secondary text-sm">Abbrechen</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Suche --}}
    <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <label class="block text-xs font-medium text-gray-500 mb-1">Suche</label>
                <input type="text" wire:model.live.debounce.300ms="search" 
                       class="w-full rounded-lg border-gray-300 text-sm"
                       placeholder="Projekt-ID, Name oder Kunde suchen...">
            </div>
            @if($statusFilter !== 'all' || $search)
                <div class="flex items-end">
                    <button wire:click="$set('statusFilter', 'all'); $set('search', '')" 
                            class="text-sm text-gray-500 hover:text-gray-700 underline py-2">
                        Filter zurücksetzen
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Projekte-Liste --}}
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projekt-ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projektname</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kunde</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($projects as $project)
                    <tr class="{{ $project->is_active ? '' : 'bg-gray-50 opacity-60' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="toggleStatus({{ $project->id }})"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors
                                           {{ $project->is_active ? 'bg-green-100 text-green-800 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                {{ $project->is_active ? 'Aktiv' : 'Inaktiv' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900">
                            {{ $project->project_id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $project->project_name }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $project->customer_name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <button wire:click="deleteProject({{ $project->id }})"
                                    wire:confirm="Projekt '{{ $project->project_name }}' wirklich entfernen?"
                                    class="text-red-600 hover:text-red-900">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            <p class="mt-2">Keine Projekte konfiguriert</p>
                            <p class="text-sm mt-1">Klicken Sie auf "Projekt hinzufügen" oder "Aus Bestand importieren"</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($projects->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $projects->links() }}
            </div>
        @endif
    </div>

    {{-- Info-Box --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
            <svg class="h-5 w-5 text-blue-400 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-blue-700">
                <p class="font-medium">Hinweis</p>
                <p class="mt-1">
                    Nur Projekte mit Status "Aktiv" werden beim Budget-Sync berücksichtigt. 
                    Projekte unter dem Kunden <strong>trafficdesign</strong> sind interne Budgets (Teamziele, Weiterbildung etc.).
                </p>
            </div>
        </div>
    </div>
</div>
