<x-app-layout>
    @section('page-title', 'Modul-Zuordnung')

    <div class="space-y-6" x-data="assignmentManager()">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Modul-Zuordnung</h1>
                <p class="text-surface-500 mt-1">Module direkt an Mitarbeitende zuweisen (unabh&auml;ngig vom Karrierepfad)</p>
            </div>
        </div>

        {{-- Assign Form --}}
        <div class="card-tool">
            <div class="card-tool-header">
                <h3 class="card-tool-title">Neues Modul zuweisen</h3>
            </div>
            <div class="card-tool-body">
                <form method="POST" action="{{ route('manage.assignments.store') }}" class="space-y-4">
                    @csrf

                    {{-- Module Selection --}}
                    <div>
                        <label class="input-label">Modul</label>
                        <select name="module_id" class="input-field" required>
                            <option value="">Modul w&auml;hlen&hellip;</option>
                            @if($globalModules->isNotEmpty())
                                <optgroup label="Allgemeine Module (ohne Karrierestufe)">
                                    @foreach($globalModules as $module)
                                        <option value="{{ $module->id }}">{{ $module->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @foreach($careerModules as $groupLabel => $groupModules)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach($groupModules as $module)
                                        <option value="{{ $module->id }}">{{ $module->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('module_id') <p class="input-error">{{ $message }}</p> @enderror
                    </div>

                    {{-- User Selection --}}
                    <div>
                        <label class="input-label">Mitarbeitende</label>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="userSearch" placeholder="Suchen&hellip;" class="input-field flex-1">
                                <button type="button" @click="selectAll()" class="btn-secondary btn-sm">Alle</button>
                                <button type="button" @click="selectNone()" class="btn-secondary btn-sm">Keine</button>
                            </div>
                            <div class="max-h-48 overflow-y-auto border border-surface-200 rounded-lg divide-y divide-surface-100">
                                @foreach($users as $user)
                                <label class="flex items-center gap-3 px-3 py-2 hover:bg-surface-50 cursor-pointer"
                                       x-show="!userSearch || '{{ strtolower($user->name) }}'.includes(userSearch.toLowerCase())"
                                       x-cloak>
                                    <input type="checkbox" name="user_ids[]" value="{{ $user->id }}" class="rounded border-surface-300 text-brand-primary focus:ring-brand-primary">
                                    <span class="text-sm text-brand-dark">{{ $user->name }}</span>
                                    @if($user->careerLevel)
                                        <span class="text-xs text-surface-400">{{ $user->careerLevel->careerPath->name }} &ndash; {{ $user->careerLevel->title }}</span>
                                    @else
                                        <span class="text-xs text-surface-400">Kein Karrierepfad</span>
                                    @endif
                                </label>
                                @endforeach
                            </div>
                        </div>
                        @error('user_ids') <p class="input-error">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Modul zuweisen
                    </button>
                </form>
            </div>
        </div>

        {{-- Current Assignments --}}
        <div class="card-tool">
            <div class="card-tool-header">
                <h3 class="card-tool-title">&Uuml;bersicht Zuordnungen</h3>
            </div>
            <div class="card-tool-body">
                @php
                    $usersWithAssignments = $users->filter(fn($u) => $u->assignedModules->isNotEmpty());
                @endphp

                @if($usersWithAssignments->isEmpty())
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <p class="empty-state-text">Noch keine Module direkt zugewiesen.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="table-tool">
                            <thead>
                                <tr>
                                    <th>Mitarbeitende/r</th>
                                    <th>Karrierepfad</th>
                                    <th>Zugewiesene Module</th>
                                    <th class="text-right">Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($usersWithAssignments as $user)
                                <tr>
                                    <td class="font-medium text-brand-dark">{{ $user->name }}</td>
                                    <td class="text-sm text-surface-500">
                                        @if($user->careerLevel)
                                            {{ $user->careerLevel->careerPath->name }} &ndash; {{ $user->careerLevel->title }}
                                        @else
                                            <span class="text-surface-400">&ndash;</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="flex flex-wrap gap-1.5">
                                            @foreach($user->assignedModules as $module)
                                                <span class="badge-primary">{{ $module->title }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        <div class="flex flex-wrap justify-end gap-1">
                                            @foreach($user->assignedModules as $module)
                                                <form method="POST" action="{{ route('manage.assignments.destroy', [$user, $module]) }}" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger btn-xs" title="{{ $module->title }} entfernen">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        {{ Str::limit($module->title, 20) }}
                                                    </button>
                                                </form>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function assignmentManager() {
            return {
                userSearch: '',
                selectAll() {
                    this.$root.querySelectorAll('input[name="user_ids[]"]').forEach(cb => {
                        if (cb.closest('label').style.display !== 'none') cb.checked = true;
                    });
                },
                selectNone() {
                    this.$root.querySelectorAll('input[name="user_ids[]"]').forEach(cb => cb.checked = false);
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
