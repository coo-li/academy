<x-app-layout>
    @section('page-title', 'Karriere-Matrix')

    <div class="space-y-6">
        {{-- Flash Messages --}}
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Karriere-Matrix</h1>
                <p class="text-surface-500 mt-1">Personio-Positionen den Academy-Karrierepfaden zuordnen</p>
            </div>
            <form method="POST" action="{{ route('admin.matrix.sync') }}">
                @csrf
                <button type="submit" class="btn-primary" onclick="this.disabled=true; this.innerHTML='<span class=\'spinner-sm\'></span> Sync läuft...'; this.form.submit();">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Personio Sync starten
                </button>
            </form>
        </div>

        {{-- Stats Row --}}
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="stat-card">
                <div class="stat-label">Personio-User</div>
                <div class="stat-value">{{ $stats['total_personio_users'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Kombinationen</div>
                <div class="stat-value">{{ $stats['total_combinations'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Zugeordnet</div>
                <div class="stat-value text-ui-success">{{ $stats['mapped'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Auto-Matched</div>
                <div class="stat-value text-brand-primary">{{ $stats['auto_matched'] }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Ohne Zuordnung</div>
                <div class="stat-value {{ $stats['unmapped'] > 0 ? 'text-ui-error' : 'text-ui-success' }}">{{ $stats['unmapped'] }}</div>
            </div>
        </div>

        {{-- Last Sync Status --}}
        @if($lastSync)
        <div class="alert-{{ $lastSync->isSuccess() ? 'success' : ($lastSync->status === 'partial' ? 'warning' : 'error') }}">
            <svg class="alert-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                @if($lastSync->isSuccess())
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                @else
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                @endif
            </svg>
            <div class="alert-content">
                <div class="alert-title">Letzter Sync: {{ $lastSync->started_at->format('d.m.Y, H:i') }} Uhr</div>
                <div>
                    {{ $lastSync->employees_fetched }} Mitarbeiter abgerufen &middot;
                    {{ $lastSync->users_created }} erstellt &middot;
                    {{ $lastSync->users_updated }} aktualisiert
                    @if($lastSync->duration())
                    &middot; Dauer: {{ $lastSync->duration() }}
                    @endif
                </div>
                @if($lastSync->error_message)
                <div class="mt-1 text-sm opacity-80">{{ Str::limit($lastSync->error_message, 200) }}</div>
                @endif
            </div>
        </div>
        @else
        <x-alert type="info" title="Noch kein Sync durchgeführt">
            Starte den ersten Sync, um Personio-Positionen zu importieren.
        </x-alert>
        @endif

        {{-- Warnings --}}
        @if($stats['users_without_path'] > 0)
        <x-alert type="warning" title="{{ $stats['users_without_path'] }} Mitarbeiter ohne Karrierepfad">
            Diese Personio-User haben noch keinen zugewiesenen Karrierepfad. Ordne die fehlenden Kombinationen unten zu.
        </x-alert>
        @endif

        @if($stats['structure_gaps'] > 0)
        <x-alert type="error" title="{{ $stats['structure_gaps'] }} Kombinationen mit Struktur-Lücken">
            Für diese Kombinationen sind Karrierepfade zugeordnet, aber es existieren keine Module in der Strukturverwaltung.
        </x-alert>
        @endif

        {{-- Position Mapping Table --}}
        <div class="card-tool" x-data="matrixTable()">
            <div class="card-tool-header">
                <h2 class="font-semibold">Positions-Zuordnung</h2>
                <span class="badge-neutral">{{ $mappings->count() }} Kombinationen</span>
            </div>
            <div class="card-tool-body !p-0">
                @if($mappings->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="table-tool">
                        <thead>
                            <tr>
                                <th>Personio-Position</th>
                                <th>Karrierestufe</th>
                                <th>Career Path</th>
                                <th class="text-center">MA</th>
                                <th>Academy-Zuordnung</th>
                                <th>Status</th>
                                <th class="text-right">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $lastPosition = null; @endphp
                            @foreach($mappings as $mapping)
                            @php
                                $comboKey = $mapping->personio_position . '|' . $mapping->personio_level_raw . '|' . $mapping->personio_path_raw;
                                $userCount = $userCounts[$comboKey]->cnt ?? 0;
                                $isMapped = $mapping->isMapped();
                                $hasStructureGap = isset($structureGaps[$mapping->id]);
                                $isAutoMatched = $mapping->is_auto_matched;
                                $hasPersonioData = filled($mapping->personio_path_raw) || filled($mapping->personio_level_raw);
                                $needsAttention = !$isMapped && $hasPersonioData;
                                $isNewPosition = $lastPosition !== $mapping->personio_position;
                                $lastPosition = $mapping->personio_position;
                            @endphp
                            <tr class="{{ $needsAttention ? 'bg-ui-warning-light' : (!$isMapped ? 'bg-ui-error-light' : '') }} {{ !$isNewPosition ? 'border-t border-surface-100' : '' }}">
                                <td>
                                    @if($isNewPosition)
                                        <span class="font-medium text-brand-dark">{{ $mapping->personio_position }}</span>
                                    @else
                                        <span class="text-surface-300">↳</span>
                                    @endif
                                </td>
                                <td>
                                    @if($mapping->personio_level_raw)
                                        <span class="badge-neutral">{{ $mapping->personio_level_raw }}</span>
                                    @else
                                        <span class="text-surface-400 italic text-sm">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($mapping->personio_path_raw)
                                        @foreach(explode(',', $mapping->personio_path_raw) as $pathTag)
                                            <span class="badge-primary">{{ trim($pathTag) }}</span>
                                        @endforeach
                                    @else
                                        <span class="text-surface-400 italic text-sm">—</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge-neutral">{{ $userCount }}</span>
                                </td>
                                <td>
                                    @if($isMapped)
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-sm font-medium text-brand-dark">{{ $mapping->careerPath?->name }}</span>
                                            <span class="text-surface-400">&rarr;</span>
                                            <span class="text-sm text-surface-600">{{ $mapping->careerLevel?->title }}</span>
                                        </div>
                                    @else
                                        <span class="text-sm text-ui-error italic">Nicht zugeordnet</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!$isMapped)
                                        <span class="badge-error">Offen</span>
                                    @elseif($hasStructureGap)
                                        <span class="badge-error" title="Keine Schulungen für dieses Level hinterlegt">Lücke: Keine Schulungen</span>
                                    @elseif($isAutoMatched)
                                        <span class="badge-info">Automatisch</span>
                                    @else
                                        <span class="badge-success">Manuell</span>
                                    @endif
                                </td>
                                <td class="text-right">
                                    <button
                                        @click="openModal({{ $mapping->id }}, '{{ addslashes($mapping->personio_position) }}', '{{ addslashes($mapping->personio_level_raw ?? '') }}', '{{ addslashes($mapping->personio_path_raw ?? '') }}', {{ $mapping->career_level_id ?? 'null' }})"
                                        class="btn-secondary btn-xs"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Zuordnen
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="p-6">
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                        </svg>
                        <div class="empty-state-title">Keine Positionen vorhanden</div>
                        <div class="empty-state-description">
                            Starte einen Personio-Sync, um die Positionen deiner Mitarbeiter zu importieren.
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Mapping Modal --}}
            <div
                x-show="showModal"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="modal-backdrop"
                @keydown.escape.window="showModal = false"
            >
                <div
                    class="modal"
                    @click.away="showModal = false"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                >
                    <div class="modal-header">
                        <h3 class="modal-title">Position zuordnen</h3>
                        <button @click="showModal = false" class="btn-ghost btn-icon btn-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <form :action="formAction" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-body space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="label">Position</label>
                                    <div class="font-medium text-brand-dark" x-text="currentPosition"></div>
                                </div>
                                <div>
                                    <label class="label">Karrierestufe</label>
                                    <div class="text-surface-600" x-text="currentLevel || '—'"></div>
                                </div>
                                <div>
                                    <label class="label">Career Path</label>
                                    <div class="text-surface-600" x-text="currentPath || '—'"></div>
                                </div>
                            </div>
                            <div>
                                <label class="label label-required">Academy Karrierepfad & Level</label>
                                <select name="career_level_id" class="select-field" x-model="selectedLevelId">
                                    <option value="">– Keine Zuordnung –</option>
                                    @foreach($careerPaths as $path)
                                        <optgroup label="{{ $path->name }}">
                                            @foreach($path->levels as $level)
                                                <option value="{{ $level->id }}">
                                                    Level {{ $level->level_number }}: {{ $level->title }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                <p class="help-text">Gilt permanent für alle Mitarbeiter mit dieser exakten Kombination aus Position, Karrierestufe und Career Path.</p>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" @click="showModal = false" class="btn-secondary">Abbrechen</button>
                            <button type="submit" class="btn-primary">Mapping speichern</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Legend --}}
        <div class="card-tool">
            <div class="card-tool-body">
                <h3 class="font-semibold text-brand-dark mb-3">Legende</h3>
                <div class="flex flex-wrap gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded" style="background-color: #FEF3C7;"></span>
                        <span class="text-surface-600">Personio-Daten vorhanden, aber nicht zuordenbar</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-4 h-4 rounded" style="background-color: #FEE2E2;"></span>
                        <span class="text-surface-600">Kein Pfad zugeordnet</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge-info">Automatisch</span>
                        <span class="text-surface-600">Auto-Match über Career Path Name</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge-success">Manuell</span>
                        <span class="text-surface-600">Vom Admin zugeordnet</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="badge-error">Lücke</span>
                        <span class="text-surface-600">Zugeordnet, aber keine Module hinterlegt</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function matrixTable() {
            return {
                showModal: false,
                currentMappingId: null,
                currentPosition: '',
                currentLevel: '',
                currentPath: '',
                selectedLevelId: '',

                get formAction() {
                    return '{{ url("admin/matrix") }}/' + this.currentMappingId;
                },

                openModal(mappingId, position, level, path, currentLevelId) {
                    this.currentMappingId = mappingId;
                    this.currentPosition = position;
                    this.currentLevel = level;
                    this.currentPath = path;
                    this.selectedLevelId = currentLevelId ?? '';
                    this.showModal = true;
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
