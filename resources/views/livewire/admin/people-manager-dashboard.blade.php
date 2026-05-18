@section('page-title', 'Team-Dashboard')

<div class="space-y-6">
    {{-- Filter-Bereich --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="flex flex-wrap items-center gap-4">
            <div class="w-48">
                <label for="year" class="block text-sm font-medium text-gray-700 mb-1">Jahr</label>
                <select wire:model.live="selectedYear" id="year"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    @foreach($availableYears as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-64">
                <label for="team" class="block text-sm font-medium text-gray-700 mb-1">Team</label>
                <select wire:model.live="selectedTeamId" id="team"
                        class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Alle meine Teams</option>
                    @foreach($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 flex items-end justify-end">
                <p class="text-sm text-gray-500">
                    {{ $grouped['counts']['total'] }} Mitarbeiter
                </p>
            </div>
        </div>
    </div>

    {{-- Team-Karten (klickbar zur Team-Budget-Übersicht) --}}
    @if($teams->isNotEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-primary-600 to-primary-700 text-white">
                <h2 class="font-semibold text-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Teams (Budget-Übersichten)
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($teams as $team)
                        <a href="{{ route('admin.team-budget', $team->id) }}"
                           class="block p-4 bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 rounded-xl hover:border-primary-400 hover:shadow-md transition-all group">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-semibold text-gray-900 group-hover:text-primary-700">
                                        {{ $team->name }}
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $team->users->count() }} Mitarbeiter
                                    </p>
                                </div>
                                <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 group-hover:bg-primary-200">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Schnell-Übersicht Karten --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $grouped['counts']['total'] }}</p>
                    <p class="text-xs text-gray-500">Mitarbeiter gesamt</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-red-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                    <span class="w-4 h-4 rounded-full bg-red-500"></span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-red-600">{{ $grouped['counts']['red'] }}</p>
                    <p class="text-xs text-gray-500">Zugehen auf</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-yellow-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                    <span class="w-4 h-4 rounded-full bg-yellow-500"></span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-yellow-600">{{ $grouped['counts']['yellow'] }}</p>
                    <p class="text-xs text-gray-500">Reminder</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border-2 border-green-200 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                    <span class="w-4 h-4 rounded-full bg-green-500"></span>
                </div>
                <div>
                    <p class="text-2xl font-bold text-green-600">{{ $grouped['counts']['green'] }}</p>
                    <p class="text-xs text-gray-500">Top</p>
                </div>
            </div>
        </div>
    </div>

    {{-- DEINE TO-DOS Sektion --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 bg-gradient-to-r from-slate-700 to-slate-800 text-white">
            <h2 class="font-semibold text-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Deine To-Dos
            </h2>
        </div>

        {{-- ROT: Zugehen auf --}}
        @if($grouped['counts']['red'] > 0)
            <div class="border-b border-gray-200">
                <div class="px-6 py-3 bg-red-50 border-b border-red-100">
                    <h3 class="font-semibold text-red-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500"></span>
                        ZUGEHEN AUF ({{ $grouped['counts']['red'] }} Mitarbeiter)
                        <span class="text-xs font-normal text-red-600 ml-2">Intervention nötig</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grouped['red'] as $employee)
                        <a href="{{ route('admin.employee-budget', $employee['user_id']) }}" 
                           class="flex items-center justify-between px-6 py-4 hover:bg-red-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-red-700 font-medium">
                                    {{ substr($employee['user_name'], 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 group-hover:text-red-700">{{ $employee['user_name'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $employee['team_name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-red-600">{{ number_format($employee['utilization_rate'], 0) }}%</p>
                                    <p class="text-xs text-gray-500">{{ number_format($employee['actual_hours'], 1) }}h / {{ number_format($employee['target_hours'], 1) }}h</p>
                                </div>
                                <div class="w-48 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $employee['action_text'] }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- GELB: Reminder --}}
        @if($grouped['counts']['yellow'] > 0)
            <div class="border-b border-gray-200">
                <div class="px-6 py-3 bg-yellow-50 border-b border-yellow-100">
                    <h3 class="font-semibold text-yellow-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                        REMINDER ({{ $grouped['counts']['yellow'] }} Mitarbeiter)
                        <span class="text-xs font-normal text-yellow-600 ml-2">Sollten sich melden</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grouped['yellow'] as $employee)
                        <a href="{{ route('admin.employee-budget', $employee['user_id']) }}" 
                           class="flex items-center justify-between px-6 py-4 hover:bg-yellow-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center text-yellow-700 font-medium">
                                    {{ substr($employee['user_name'], 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 group-hover:text-yellow-700">{{ $employee['user_name'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $employee['team_name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-yellow-600">{{ number_format($employee['utilization_rate'], 0) }}%</p>
                                    <p class="text-xs text-gray-500">{{ number_format($employee['actual_hours'], 1) }}h / {{ number_format($employee['target_hours'], 1) }}h</p>
                                </div>
                                <div class="w-48 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        {{ $employee['action_text'] }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- GRÜN: Top --}}
        @if($grouped['counts']['green'] > 0)
            <div>
                <div class="px-6 py-3 bg-green-50 border-b border-green-100">
                    <h3 class="font-semibold text-green-800 flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500"></span>
                        TOP ({{ $grouped['counts']['green'] }} Mitarbeiter)
                        <span class="text-xs font-normal text-green-600 ml-2">Alles im grünen Bereich</span>
                    </h3>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($grouped['green'] as $employee)
                        <a href="{{ route('admin.employee-budget', $employee['user_id']) }}" 
                           class="flex items-center justify-between px-6 py-4 hover:bg-green-50 transition-colors group">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center text-green-700 font-medium">
                                    {{ substr($employee['user_name'], 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 group-hover:text-green-700">{{ $employee['user_name'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $employee['team_name'] }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6">
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-green-600">{{ number_format($employee['utilization_rate'], 0) }}%</p>
                                    <p class="text-xs text-gray-500">{{ number_format($employee['actual_hours'], 1) }}h / {{ number_format($employee['target_hours'], 1) }}h</p>
                                </div>
                                <div class="w-48 text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $employee['action_text'] }}
                                    </span>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Keine Mitarbeiter --}}
        @if($grouped['counts']['total'] === 0)
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Mitarbeiter</h3>
                <p class="mt-1 text-sm text-gray-500">Es sind dir noch keine Mitarbeiter zugeordnet.</p>
            </div>
        @endif
    </div>

    {{-- Ampel-Legende --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
        <div class="flex flex-wrap items-center gap-6 text-sm">
            <span class="font-medium text-gray-700">Ampel-Legende:</span>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-green-500"></span>
                <span class="text-gray-600">&ge; 90% - Top Auslastung</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                <span class="text-gray-600">75-90% - Ziel-Check empfohlen</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full bg-red-500"></span>
                <span class="text-gray-600">&lt; 75% - Intervention nötig</span>
            </div>
        </div>
    </div>
</div>
