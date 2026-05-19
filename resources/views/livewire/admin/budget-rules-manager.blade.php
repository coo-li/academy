@section('page-title', 'Budget-Regeln')

<div>
    <div class="mb-6">
        <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Budget-Regeln</h1>
        <p class="text-surface-500 mt-1.5 text-base">Definiere unterschiedliche Weiterbildungsbudgets für verschiedene Mitarbeitergruppen.</p>
    </div>

    @if (session()->has('message'))
        <div class="mb-4 p-4 bg-green-100 border border-green-200 text-green-800 rounded-lg">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 p-4 bg-red-100 border border-red-200 text-red-800 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    {{-- Statistik-Übersicht Basis-Regeln --}}
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Verteilung nach Basis-Regeln (Gesamt-Budget)</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @foreach ($statistics as $stat)
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ $stat['user_count'] }}</div>
                    <div class="text-sm text-gray-600">{{ $stat['rule']?->name ?? $stat['rule_name'] ?? 'Unbekannt' }}</div>
                    <div class="text-xs text-gray-500 mt-1">{{ number_format($stat['total_budget'], 0, ',', '.') }} €</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Statistik-Übersicht Overlay-Regeln --}}
    @if (count($overlayStatistics) > 0)
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Overlay-Regeln (Cash-Limits)</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                @foreach ($overlayStatistics as $stat)
                    <div class="bg-orange-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-orange-600">{{ $stat['user_count'] }}</div>
                        <div class="text-sm text-gray-600">{{ $stat['rule']->name }}</div>
                        <div class="text-xs text-gray-500 mt-1">Max. {{ number_format($stat['cash_limit'], 0, ',', '.') }} € Cash</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Aktionen --}}
    <div class="flex justify-between items-center mb-4">
        <button wire:click="openCreate" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            + Neue Regel
        </button>
        <button wire:click="syncBudgets" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
            Alle Budgets synchronisieren
        </button>
    </div>

    {{-- Regeln-Liste --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priorität</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bedingungen</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($rules as $rule)
                    <tr class="{{ !$rule->is_active ? 'opacity-50 bg-gray-50' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $rule->priority }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $rule->name }}</div>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @if ($rule->isBase())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Basis</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-800">Overlay</span>
                                @endif
                                @if ($rule->is_default)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">Standard</span>
                                @endif
                            </div>
                            @if ($rule->description)
                                <div class="text-xs text-gray-500 mt-1">{{ Str::limit($rule->description, 50) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($rule->isBase())
                                <div class="text-sm font-semibold text-gray-900">{{ number_format($rule->max_money_budget ?? 0, 0, ',', '.') }} €</div>
                                @if ($rule->hourly_rate_override)
                                    <div class="text-xs text-gray-500">Stundensatz: {{ number_format($rule->hourly_rate_override, 0, ',', '.') }} €</div>
                                @endif
                            @else
                                <div class="text-sm font-semibold text-orange-600">Max. {{ number_format($rule->max_cash_budget ?? 0, 0, ',', '.') }} € Cash</div>
                                <div class="text-xs text-gray-500">Cash-Limit</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs text-gray-600 space-y-1">
                                @if (!empty($rule->applies_to_departments))
                                    <div>Dept: {{ implode(', ', $rule->applies_to_departments) }}</div>
                                @endif
                                @if (!empty($rule->applies_to_positions))
                                    <div>Pos: {{ implode(', ', $rule->applies_to_positions) }}</div>
                                @endif
                                @if (!empty($rule->applies_to_levels))
                                    <div>Level: {{ implode(', ', $rule->applies_to_levels) }}</div>
                                @endif
                                @if ($rule->working_hours_min !== null || $rule->working_hours_max !== null)
                                    <div>Stunden: {{ $rule->working_hours_min ?? 0 }}h - {{ $rule->working_hours_max ?? '∞' }}h</div>
                                @endif
                                @if ($rule->is_default && empty($rule->applies_to_departments) && empty($rule->applies_to_positions))
                                    <span class="text-gray-400 italic">Fallback für alle</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button wire:click="toggleActive({{ $rule->id }})" class="inline-flex items-center">
                                @if ($rule->is_active)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Aktiv</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inaktiv</span>
                                @endif
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <button wire:click="edit({{ $rule->id }})" class="text-blue-600 hover:text-blue-900 mr-3">Bearbeiten</button>
                            @unless ($rule->is_default)
                                <button wire:click="delete({{ $rule->id }})" wire:confirm="Diese Regel wirklich löschen?" class="text-red-600 hover:text-red-900">Löschen</button>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Keine Regeln definiert. Erstelle eine Standard-Regel.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal/Formular --}}
    @if ($showForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click.self="closeForm">
            <div class="relative top-10 mx-auto p-6 border w-full max-w-2xl shadow-lg rounded-lg bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ $editMode ? 'Regel bearbeiten' : 'Neue Regel erstellen' }}
                    </h3>
                    <button wire:click="closeForm" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form wire:submit="save" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name *</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priorität *</label>
                            <input type="number" wire:model="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <p class="text-xs text-gray-500 mt-1">Höhere Zahl = wird zuerst geprüft</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Beschreibung</label>
                        <textarea wire:model="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                    </div>

                    {{-- Regel-Typ Auswahl --}}
                    <div class="p-4 bg-gray-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Regel-Typ *</label>
                        <div class="flex gap-4">
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer {{ $rule_type === 'base' ? 'bg-blue-50 border-blue-500' : 'bg-white border-gray-200' }}">
                                <input type="radio" wire:model.live="rule_type" value="base" class="text-blue-600 focus:ring-blue-500" />
                                <span class="ml-2">
                                    <span class="font-medium text-gray-900">Basis-Regel</span>
                                    <span class="block text-xs text-gray-500">Definiert Gesamt-Budget + Stundensatz</span>
                                </span>
                            </label>
                            <label class="flex items-center p-3 border rounded-lg cursor-pointer {{ $rule_type === 'overlay' ? 'bg-orange-50 border-orange-500' : 'bg-white border-gray-200' }}">
                                <input type="radio" wire:model.live="rule_type" value="overlay" class="text-orange-600 focus:ring-orange-500" />
                                <span class="ml-2">
                                    <span class="font-medium text-gray-900">Overlay-Regel</span>
                                    <span class="block text-xs text-gray-500">Setzt nur Cash-Limit (zusätzlich zur Basis)</span>
                                </span>
                            </label>
                        </div>
                        @error('rule_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    {{-- Basis-spezifische Felder --}}
                    @if ($rule_type === 'base')
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Max. Geld-Budget (€) *</label>
                                <input type="number" step="0.01" wire:model="max_money_budget" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                <p class="text-xs text-gray-500 mt-1">Gesamt-Weiterbildungsbudget</p>
                                @error('max_money_budget') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Stundensatz-Override (€)</label>
                                <input type="number" step="0.01" wire:model="hourly_rate_override" placeholder="Überschreibt Karrierestufe" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            </div>
                        </div>
                    @endif

                    {{-- Overlay-spezifische Felder --}}
                    @if ($rule_type === 'overlay')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Max. Cash-Budget (€) *</label>
                            <input type="number" step="0.01" wire:model="max_cash_budget" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500" />
                            <p class="text-xs text-gray-500 mt-1">Maximaler Betrag, der als echtes Geld ausgegeben werden darf</p>
                            @error('max_cash_budget') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Min. Wochenstunden</label>
                            <input type="number" step="0.5" wire:model="working_hours_min" placeholder="z.B. 20" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Max. Wochenstunden</label>
                            <input type="number" step="0.5" wire:model="working_hours_max" placeholder="z.B. 30" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                        </div>
                    </div>

                    {{-- Departments --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gilt für Departments (Personio)</label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" wire:model="newDepartment" wire:keydown.enter.prevent="addDepartment" placeholder="Department hinzufügen..." class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <button type="button" wire:click="addDepartment" class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">+</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($applies_to_departments as $index => $dept)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                                    {{ $dept }}
                                    <button type="button" wire:click="removeDepartment({{ $index }})" class="ml-1 text-blue-600 hover:text-blue-900">&times;</button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Positions --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gilt für Positionen (enthält)</label>
                        <div class="flex gap-2 mb-2">
                            <input type="text" wire:model="newPosition" wire:keydown.enter.prevent="addPosition" placeholder="Position hinzufügen..." class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                            <button type="button" wire:click="addPosition" class="px-3 py-2 bg-gray-200 rounded-md hover:bg-gray-300">+</button>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($applies_to_positions as $index => $pos)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-sm bg-green-100 text-green-800">
                                    {{ $pos }}
                                    <button type="button" wire:click="removePosition({{ $index }})" class="ml-1 text-green-600 hover:text-green-900">&times;</button>
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <div class="flex items-center gap-6">
                        @if ($rule_type === 'base')
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="is_default" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                                <span class="ml-2 text-sm text-gray-700">Standard-Regel (Fallback)</span>
                            </label>
                        @endif
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                            <span class="ml-2 text-sm text-gray-700">Aktiv</span>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t">
                        <button type="button" wire:click="closeForm" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Abbrechen</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            {{ $editMode ? 'Speichern' : 'Erstellen' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
