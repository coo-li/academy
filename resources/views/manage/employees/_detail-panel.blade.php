{{-- Employee Detail Panel (rendered server-side, injected via AJAX) --}}
<div class="flex flex-col h-full" id="emp-detail-panel" data-user-id="{{ $user->id }}">

    {{-- Header --}}
    <div class="p-6 border-b border-surface-200 bg-surface-50">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="avatar-lg">
                    <span>{{ mb_strtoupper(mb_substr($user->name, 0, 2)) }}</span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-brand-dark">{{ $user->name }}</h2>
                    <div class="flex items-center gap-2 mt-1">
                        @if($user->team)
                            <span class="badge-info">{{ $user->team->name }}</span>
                        @endif
                        @if($user->careerLevel)
                            <span class="badge-primary">{{ $user->careerLevel->careerPath->name }} &ndash; {{ $user->careerLevel->title }}</span>
                        @else
                            <span class="badge-warning">Kein Karrierepfad</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Suggestions --}}
    @if(!empty($suggestions))
    <div class="px-6 pt-4">
        @foreach($suggestions as $suggestion)
            <div class="alert-info mb-3">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-ui-info flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-medium">{{ $suggestion['message'] }}</p>
                        @if($suggestion['type'] === 'next_level')
                            <button type="button"
                                    class="btn-primary btn-sm mt-2"
                                    onclick="empDetailAction('{{ route('manage.employees.assignCareerLevel', $user) }}', 'PATCH', {career_level_id: '{{ $suggestion['career_level_id'] }}'})">
                                Auf n&auml;chste Stufe hochstufen
                            </button>
                        @endif
                        @if($suggestion['type'] === 'missing_modules' && !empty($suggestion['modules']))
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($suggestion['modules'] as $id => $title)
                                    <span class="badge-warning text-xs">{{ $title }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Career Path Section --}}
    <div class="px-6 pt-4">
        <div class="card-tool">
            <div class="card-tool-header flex items-center justify-between">
                <h3 class="card-tool-title">Karrierepfad</h3>
            </div>
            <div class="card-tool-body space-y-3">
                @if($user->careerLevel)
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-brand-dark">{{ $user->careerLevel->careerPath->name }}</span>
                            <span class="text-surface-500">&ndash; {{ $user->careerLevel->title }}</span>
                        </div>
                        <button type="button"
                                class="btn-danger btn-xs"
                                onclick="if(confirm('Karrierepfad wirklich entfernen?')) empDetailAction('{{ route('manage.employees.removeCareerPath', $user) }}', 'DELETE')">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            Entfernen
                        </button>
                    </div>
                @endif

                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="input-label">{{ $user->careerLevel ? 'Karrierestufe &auml;ndern' : 'Karrierepfad zuweisen' }}</label>
                        <select id="career-level-select" class="input-field w-full">
                            <option value="">Pfad &amp; Stufe w&auml;hlen&hellip;</option>
                            @foreach($careerPaths as $path)
                                <optgroup label="{{ $path->name }}">
                                    @foreach($path->levels as $level)
                                        <option value="{{ $level->id }}"
                                            {{ $user->career_level_id === $level->id ? 'selected' : '' }}>
                                            {{ $level->title }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn-primary btn-sm whitespace-nowrap"
                            onclick="var v=document.getElementById('career-level-select').value; if(v) empDetailAction('{{ route('manage.employees.assignCareerLevel', $user) }}', 'PATCH', {career_level_id: v})">
                        Zuweisen
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Current Modules Section --}}
    <div class="px-6 pt-4">
        <div class="card-tool">
            <div class="card-tool-header flex items-center justify-between">
                <h3 class="card-tool-title">Zugewiesene Module ({{ $allModules->count() }})</h3>
                @if(count($disabledModuleIds) > 0)
                    <span class="badge-warning text-xs">{{ count($disabledModuleIds) }} deaktiviert</span>
                @endif
            </div>
            <div class="card-tool-body p-0">
                @if($allModules->isEmpty() && empty($disabledModuleIds))
                    <div class="empty-state py-8">
                        <p class="empty-state-text">Keine Module zugewiesen.</p>
                    </div>
                @else
                    <div class="divide-y divide-surface-100">
                        @foreach($allModules as $module)
                            @php
                                $isDirectAssignment = in_array($module->id, $assignedModuleIds);
                                $isFromCareer = $careerModules->contains('id', $module->id);
                                $enrollment = $enrollmentMap[$module->id] ?? null;
                                $isCompleted = $enrollment && $enrollment->status === 'completed';
                            @endphp
                            <div class="flex items-center justify-between px-4 py-3 {{ $isCompleted ? 'bg-green-50/50' : '' }}">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    @if($isCompleted)
                                        <svg class="w-5 h-5 text-ui-success flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-surface-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium text-brand-dark truncate">{{ $module->title }}</div>
                                        <div class="flex items-center gap-1.5 mt-0.5">
                                            @if($isFromCareer && !$isDirectAssignment)
                                                <span class="text-xs text-surface-400">Aus Karrierepfad</span>
                                            @elseif($isDirectAssignment && !$isFromCareer)
                                                <span class="text-xs text-brand-primary">Direkt zugewiesen</span>
                                            @else
                                                <span class="text-xs text-surface-400">Karrierepfad + Direkt</span>
                                            @endif
                                            @if($module->skillCategory)
                                                <span class="text-xs text-surface-300">&middot;</span>
                                                <span class="text-xs text-surface-400">{{ $module->skillCategory->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                    @if($enrollment)
                                        <span class="badge-{{ $isCompleted ? 'success' : 'warning' }} text-xs">
                                            {{ $isCompleted ? 'Abgeschlossen' : ucfirst($enrollment->status) }}
                                        </span>
                                    @endif
                                    @if($isFromCareer && !$isDirectAssignment)
                                        <button type="button"
                                                class="btn-warning btn-xs"
                                                title="Standard-Modul deaktivieren"
                                                onclick="if(confirm('Modul &quot;{{ $module->title }}&quot; f\u00fcr diesen Mitarbeiter deaktivieren?')) empDetailAction('{{ route('manage.employees.disableCareerModule', [$user, $module]) }}', 'POST')">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                            </svg>
                                        </button>
                                    @endif
                                    @if($isDirectAssignment)
                                        <button type="button"
                                                class="btn-danger btn-xs"
                                                title="Zuweisung entfernen"
                                                onclick="empDetailAction('{{ route('manage.employees.removeModule', [$user, $module]) }}', 'DELETE')">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Disabled Career Modules Section --}}
    @php
        $disabledModules = $careerModules->filter(fn ($m) => in_array($m->id, $disabledModuleIds));
    @endphp
    @if($disabledModules->isNotEmpty())
    <div class="px-6 pt-4">
        <div class="card-tool border-surface-200 bg-surface-50/50">
            <div class="card-tool-header flex items-center justify-between">
                <h3 class="card-tool-title text-surface-400">Deaktivierte Standard-Module ({{ $disabledModules->count() }})</h3>
            </div>
            <div class="card-tool-body p-0">
                <div class="divide-y divide-surface-100">
                    @foreach($disabledModules as $module)
                        <div class="flex items-center justify-between px-4 py-3 opacity-60">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <svg class="w-5 h-5 text-surface-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-surface-400 truncate line-through">{{ $module->title }}</div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-xs text-surface-400">Aus Karrierepfad &ndash; deaktiviert</span>
                                        @if($module->skillCategory)
                                            <span class="text-xs text-surface-300">&middot;</span>
                                            <span class="text-xs text-surface-400">{{ $module->skillCategory->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                <button type="button"
                                        class="btn-primary btn-xs"
                                        title="Modul wieder aktivieren"
                                        onclick="empDetailAction('{{ route('manage.employees.enableCareerModule', [$user, $module]) }}', 'DELETE')">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Aktivieren
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Add Module Section --}}
    <div class="px-6 py-4">
        <div class="card-tool">
            <div class="card-tool-header">
                <h3 class="card-tool-title">Modul hinzuf&uuml;gen</h3>
            </div>
            <div class="card-tool-body">
                <div class="flex items-end gap-2">
                    <div class="flex-1">
                        <label class="input-label">Modul ausw&auml;hlen</label>
                        <select id="module-assign-select" class="input-field w-full">
                            <option value="">Modul w&auml;hlen&hellip;</option>
                            @php
                                $existingIds = $allModules->pluck('id')->toArray();
                                $globalModules = $availableModules->where('career_level_id', null)->whereNotIn('id', $existingIds);
                                $pathModules = $availableModules->where('career_level_id', '!=', null)->whereNotIn('id', $existingIds)
                                    ->groupBy(fn ($m) => $m->careerLevel->careerPath->name . ' – ' . $m->careerLevel->title);
                            @endphp
                            @if($globalModules->isNotEmpty())
                                <optgroup label="Allgemeine Module">
                                    @foreach($globalModules as $module)
                                        <option value="{{ $module->id }}">{{ $module->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            @foreach($pathModules as $groupLabel => $groupModules)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach($groupModules as $module)
                                        <option value="{{ $module->id }}">{{ $module->title }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="btn-primary btn-sm whitespace-nowrap"
                            onclick="var v=document.getElementById('module-assign-select').value; if(v) empDetailAction('{{ route('manage.employees.assignModule', $user) }}', 'POST', {module_id: v})">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Zuweisen
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
