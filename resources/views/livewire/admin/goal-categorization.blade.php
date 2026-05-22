@section('page-title', 'Ziele kategorisieren')

<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Ziele kategorisieren</h1>
            <p class="text-surface-500 mt-1.5 text-base">
                Priorisierung der Ziele für td
                @if($showAllTeams)
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-800 rounded-full">Alle Teams</span>
                @endif
            </p>
        </div>
        <div class="text-sm text-gray-500">
            Änderungen werden automatisch gespeichert
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Jahr</label>
                <select wire:model.live="selectedYear" id="year"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="team" class="block text-sm font-medium text-gray-700 mb-1">Team</label>
                <select wire:model.live="selectedTeamId" id="team"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">{{ $showAllTeams ? 'Alle Teams' : 'Meine Teams' }}</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Zieltyp</label>
                <select wire:model.live="selectedType" id="type"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Alle Typen</option>
                    @foreach($typeLabels as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="categoryFilter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select wire:model.live="categoryFilter" id="categoryFilter"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Alle</option>
                    <option value="uncategorized">Noch nicht bearbeitet</option>
                    <option value="A">A-Ziel</option>
                    <option value="B">B-Ziel</option>
                    <option value="C">C-Ziel</option>
                    <option value="none">Keine Kategorie</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-7 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
            <p class="text-xs text-gray-500">Ziele gesamt</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-green-200 p-4">
            <p class="text-2xl font-bold text-green-600">{{ $stats['categorized'] }}</p>
            <p class="text-xs text-gray-500">Bearbeitet</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-orange-200 p-4">
            <p class="text-2xl font-bold text-orange-600">{{ $stats['uncategorized'] }}</p>
            <p class="text-xs text-gray-500">Offen</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-red-200 p-4">
            <p class="text-2xl font-bold text-red-600">{{ $stats['category_a'] }}</p>
            <p class="text-xs text-gray-500">A-Ziele</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-200 p-4">
            <p class="text-2xl font-bold text-yellow-600">{{ $stats['category_b'] }}</p>
            <p class="text-xs text-gray-500">B-Ziele</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-gray-300 p-4">
            <p class="text-2xl font-bold text-gray-600">{{ $stats['category_c'] }}</p>
            <p class="text-xs text-gray-500">C-Ziele</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-slate-300 p-4">
            <p class="text-2xl font-bold text-slate-500">{{ $stats['category_none'] ?? 0 }}</p>
            <p class="text-xs text-gray-500">Keine Kat.</p>
        </div>
    </div>

    {{-- Progress --}}
    @if($stats['total'] > 0)
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-medium text-gray-700">Fortschritt der Kategorisierung</span>
            <span class="text-sm font-bold text-gray-900">{{ $stats['progress'] }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2.5">
            <div class="bg-green-500 h-2.5 rounded-full transition-all duration-300" style="width: {{ $stats['progress'] }}%"></div>
        </div>
    </div>
    @endif

    {{-- Goals Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mitarbeiter</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ziel</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Typ</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Stunden</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-44">Kategorie</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($goals as $goal)
                        <tr class="hover:bg-gray-50" wire:key="goal-{{ $goal->key }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 font-medium text-sm">
                                        {{ substr($goal->user->name ?? 'U', 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $goal->user->name ?? 'Unbekannt' }}</p>
                                        <p class="text-xs text-gray-500">{{ $goal->user->team->name ?? 'Kein Team' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-sm text-gray-900 font-medium">{{ $goal->budget_name ?: $goal->label }}</p>
                                @if($goal->budget_name && $goal->label && $goal->budget_name !== $goal->label)
                                    <p class="text-xs text-gray-500">{{ $goal->label }}</p>
                                @endif
                                @if($goal->entry_count > 1)
                                    <p class="text-xs text-gray-400">{{ $goal->entry_count }} Monate</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                    {{ match($goal->type) {
                                        'personal_goal' => 'bg-blue-100 text-blue-800',
                                        'team_goal' => 'bg-purple-100 text-purple-800',
                                        'internal_training' => 'bg-teal-100 text-teal-800',
                                        default => 'bg-gray-100 text-gray-800',
                                    } }}">
                                    {{ $typeLabels[$goal->type] ?? $goal->type }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">
                                {{ number_format($goal->total_hours, 1, ',', '.') }} h
                            </td>
                            <td class="px-4 py-3">
                                <select wire:change="updateCategory({{ $goal->user_id }}, '{{ addslashes($goal->budget_name) }}', $event.target.value)"
                                        class="w-full text-sm rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500
                                               {{ $goal->goal_category ? 'font-medium' : 'text-gray-400' }}
                                               {{ match($goal->goal_category) {
                                                   'A' => 'bg-red-50 border-red-300 text-red-800',
                                                   'B' => 'bg-yellow-50 border-yellow-300 text-yellow-800',
                                                   'C' => 'bg-gray-100 border-gray-300 text-gray-700',
                                                   'none' => 'bg-slate-50 border-slate-300 text-slate-600',
                                                   default => '',
                                               } }}">
                                    <option value="" {{ !$goal->goal_category ? 'selected' : '' }}>– Auswählen –</option>
                                    @foreach($goalCategories as $key => $config)
                                        <option value="{{ $key }}" {{ $goal->goal_category === $key ? 'selected' : '' }}>
                                            {{ $config['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <p class="text-gray-500 font-medium">Keine Ziele gefunden</p>
                                <p class="text-gray-400 text-sm mt-1">Passe die Filter an oder wähle ein anderes Jahr.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Legende --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Kategorien-Legende</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @foreach($goalCategories as $key => $config)
                <div class="flex items-start gap-3 p-3 rounded-lg 
                    {{ match($key) {
                        'A' => 'bg-red-50 border border-red-200',
                        'B' => 'bg-yellow-50 border border-yellow-200',
                        'C' => 'bg-gray-100 border border-gray-200',
                        'none' => 'bg-slate-50 border border-slate-200',
                        default => 'bg-gray-50 border border-gray-200',
                    } }}">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-bold
                        {{ match($key) {
                            'A' => 'bg-red-500 text-white',
                            'B' => 'bg-yellow-500 text-white',
                            'C' => 'bg-gray-500 text-white',
                            'none' => 'bg-slate-400 text-white',
                            default => 'bg-gray-400 text-white',
                        } }}">
                        {{ $key === 'none' ? '–' : $key }}
                    </span>
                    <div>
                        <p class="font-semibold text-gray-900">{{ $config['label'] }}</p>
                        <p class="text-xs text-gray-600">{{ $config['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
