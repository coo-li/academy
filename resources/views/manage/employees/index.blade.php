<x-app-layout>
    @section('page-title', $scope === 'all' ? 'Alle Mitarbeitenden' : 'Meine Mitarbeitenden')

    <div class="space-y-6" x-data="employeeManager()">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">
                    {{ $scope === 'all' ? 'Alle Mitarbeitenden' : 'Meine Mitarbeitenden' }}
                </h1>
                <p class="text-surface-500 mt-1">
                    {{ $scope === 'all' ? 'Übersicht über alle Mitarbeitenden' : 'Karrierepfade und Module für dein Team verwalten' }}
                </p>
            </div>
            <div class="flex items-center gap-2 text-sm text-surface-500">
                <span class="badge-info">{{ $employees->count() }} Mitarbeitende</span>
            </div>
        </div>

        {{-- Search & Filter --}}
        <div class="card-tool">
            <div class="card-tool-body">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text"
                               x-model="search"
                               placeholder="Name oder E-Mail suchen&hellip;"
                               class="input-field w-full">
                    </div>
                    <div class="w-full sm:w-48">
                        <select x-model="teamFilter" class="input-field w-full">
                            <option value="">Alle Teams</option>
                            @foreach($teams as $team)
                                <option value="{{ $team->name }}">{{ $team->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-full sm:w-48">
                        <select x-model="pathFilter" class="input-field w-full">
                            <option value="">Alle Karrierepfade</option>
                            <option value="__none__">Kein Karrierepfad</option>
                            @php
                                $paths = $employees->filter(fn($e) => $e->careerLevel)->pluck('careerLevel.careerPath.name')->unique()->sort();
                            @endphp
                            @foreach($paths as $path)
                                <option value="{{ $path }}">{{ $path }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Employee Table --}}
        <div class="card-tool">
            <div class="card-tool-body p-0">
                <div class="overflow-x-auto">
                    <table class="table-tool">
                        <thead>
                            <tr>
                                <th class="cursor-pointer select-none" @click="toggleSort('name')">
                                    <div class="flex items-center gap-1">
                                        Mitarbeiter/in
                                        <template x-if="sortField === 'name'">
                                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': sortDir === 'desc' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        </template>
                                    </div>
                                </th>
                                <th>E-Mail</th>
                                <th class="cursor-pointer select-none" @click="toggleSort('team')">
                                    <div class="flex items-center gap-1">
                                        Team
                                        <template x-if="sortField === 'team'">
                                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': sortDir === 'desc' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        </template>
                                    </div>
                                </th>
                                <th class="cursor-pointer select-none" @click="toggleSort('level')">
                                    <div class="flex items-center gap-1">
                                        Karrierestufe
                                        <template x-if="sortField === 'level'">
                                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': sortDir === 'desc' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        </template>
                                    </div>
                                </th>
                                <th class="cursor-pointer select-none" @click="toggleSort('path')">
                                    <div class="flex items-center gap-1">
                                        Karrierepfad
                                        <template x-if="sortField === 'path'">
                                            <svg class="w-4 h-4 transition-transform" :class="{ 'rotate-180': sortDir === 'desc' }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path></svg>
                                        </template>
                                    </div>
                                </th>
                                <th class="text-right">Module</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="emp in filteredEmployees" :key="emp.id">
                                <tr class="cursor-pointer hover:bg-surface-50 transition-colors"
                                    @click="window.location.href = '/manage/employees/' + emp.id">
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <span x-text="emp.initials"></span>
                                            </div>
                                            <span class="font-medium text-brand-dark" x-text="emp.name"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-sm text-surface-500" x-text="emp.email"></span>
                                    </td>
                                    <td>
                                        <span class="text-sm text-surface-500" x-text="emp.team || '\u2013'"></span>
                                    </td>
                                    <td>
                                        <span class="text-sm" x-text="emp.level || '\u2013'" :class="emp.level ? 'text-brand-dark' : 'text-surface-400'"></span>
                                    </td>
                                    <td>
                                        <template x-if="emp.path">
                                            <span class="badge-primary" x-text="emp.path"></span>
                                        </template>
                                        <template x-if="!emp.path">
                                            <span class="text-surface-400">&ndash;</span>
                                        </template>
                                    </td>
                                    <td class="text-right">
                                        <span class="text-sm text-surface-500" x-text="emp.moduleCount + ' Module'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <template x-if="filteredEmployees.length === 0">
                    <div class="empty-state py-12">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="empty-state-text">Keine Mitarbeitenden gefunden.</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function employeeManager() {
            return {
                search: '',
                teamFilter: '',
                pathFilter: '',
                sortField: 'name',
                sortDir: 'asc',

                employees: @json($employeesJson),

                get filteredEmployees() {
                    let result = this.employees;

                    if (this.search) {
                        const q = this.search.toLowerCase();
                        result = result.filter(e => e.name.toLowerCase().includes(q) || (e.email && e.email.toLowerCase().includes(q)));
                    }

                    if (this.teamFilter) {
                        result = result.filter(e => e.team === this.teamFilter);
                    }

                    if (this.pathFilter) {
                        if (this.pathFilter === '__none__') {
                            result = result.filter(e => !e.path);
                        } else {
                            result = result.filter(e => e.path === this.pathFilter);
                        }
                    }

                    const dir = this.sortDir === 'asc' ? 1 : -1;
                    const field = this.sortField;
                    result = [...result].sort((a, b) => {
                        const va = (a[field] || '').toLowerCase();
                        const vb = (b[field] || '').toLowerCase();
                        return va < vb ? -dir : va > vb ? dir : 0;
                    });

                    return result;
                },

                toggleSort(field) {
                    if (this.sortField === field) {
                        this.sortDir = this.sortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortField = field;
                        this.sortDir = 'asc';
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
