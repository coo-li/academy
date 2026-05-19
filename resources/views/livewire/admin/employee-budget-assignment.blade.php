@section('page-title', 'MA-Budgets zuweisen')

<div>
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">MA-Budgets zuweisen</h1>
        <p class="text-surface-500 mt-1.5 text-base">Übersicht welche Budget-Regel für jeden Mitarbeiter gilt. Manuelle Überschreibungen möglich.</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-800 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    {{-- Statistik --}}
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="flex gap-8">
            <div>
                <span class="text-2xl font-bold text-blue-600">{{ $stats['total'] }}</span>
                <span class="text-gray-600 ml-2">Mitarbeiter gesamt</span>
            </div>
            <div>
                <span class="text-2xl font-bold text-orange-600">{{ $stats['manual'] }}</span>
                <span class="text-gray-600 ml-2">Manuelle Zuordnungen</span>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Suche</label>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name, E-Mail, Position..." 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Team</label>
                <select wire:model.live="filterTeam" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Alle Teams</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Regel</label>
                <select wire:model.live="filterRule" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Alle Regeln</option>
                    @foreach ($rules as $rule)
                        <option value="{{ $rule->slug }}">{{ $rule->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="showManualOnly" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                    <span class="ml-2 text-sm text-gray-700">Nur manuelle</span>
                </label>
            </div>
        </div>
    </div>

    {{-- Tabelle --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mitarbeiter</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team / Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wochenstunden</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktive Regel</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($users as $item)
                    @php $user = $item['user']; @endphp
                    <tr class="{{ $item['is_manual'] ? 'bg-orange-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $user->team?->name ?? '-' }}</div>
                            <div class="text-xs text-gray-500">{{ $user->personio_position ?? '-' }}</div>
                            @if ($user->personio_level_raw)
                                <div class="text-xs text-gray-400">{{ $user->personio_level_raw }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($user->weekly_working_hours)
                                <span class="text-sm text-gray-900">{{ number_format($user->weekly_working_hours, 1, ',', '.') }}h</span>
                            @else
                                <span class="text-sm text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap items-center gap-1">
                                @if ($item['is_manual'])
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">
                                        Manuell
                                    </span>
                                @endif
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $item['effective_rule']?->name ?? 'Standard (Fallback)' }}
                                </span>
                                @if ($item['overlay_rules']->isNotEmpty())
                                    @foreach ($item['overlay_rules'] as $overlay)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">
                                            + {{ $overlay->name }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">{{ number_format($item['budget'], 0, ',', '.') }} €</div>
                            @if ($item['has_cash_limit'])
                                <div class="text-xs text-orange-600">Max. {{ number_format($item['max_cash'], 0, ',', '.') }} € Cash</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="openAssignModal({{ $user->id }})" class="text-blue-600 hover:text-blue-900">
                                {{ $item['is_manual'] ? 'Ändern' : 'Zuweisen' }}
                            </button>
                            @if ($item['is_manual'])
                                <button wire:click="removeManualAssignment({{ $user->id }})" class="ml-3 text-red-600 hover:text-red-900">
                                    Entfernen
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Keine Mitarbeiter gefunden.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-4">
        {{ $users->links() }}
    </div>

    {{-- Zuweisungs-Modal --}}
    @if ($showAssignModal)
        @php $assigningUser = \App\Models\User::find($assigningUserId); @endphp
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click.self="closeAssignModal">
            <div class="relative top-20 mx-auto p-6 border w-full max-w-md shadow-lg rounded-lg bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Budget-Regel zuweisen</h3>
                    <button wire:click="closeAssignModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                @if ($assigningUser)
                    <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                        <div class="font-medium">{{ $assigningUser->name }}</div>
                        <div class="text-sm text-gray-600">{{ $assigningUser->personio_position ?? 'Keine Position' }}</div>
                        <div class="text-sm text-gray-500">{{ $assigningUser->team?->name ?? 'Kein Team' }}</div>
                    </div>
                @endif

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Basis-Regel auswählen</label>
                    <select wire:model="selectedRuleId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Automatisch ermitteln</option>
                        @foreach ($rules as $rule)
                            <option value="{{ $rule->id }}">
                                {{ $rule->name }} ({{ number_format($rule->max_money_budget ?? 0, 0, ',', '.') }} €)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Bei "Automatisch" wird die Basis-Regel basierend auf Department, Position und Wochenstunden ermittelt. Overlay-Regeln (z.B. Cash-Limits) werden zusätzlich automatisch angewendet.</p>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="closeAssignModal" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                        Abbrechen
                    </button>
                    <button wire:click="assignRule" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Speichern
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
