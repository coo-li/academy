<x-app-layout>
    @section('page-title', $user->name)

    <div class="space-y-6">

        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Back + Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('manage.employees.index') }}" class="btn-secondary btn-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Zur&uuml;ck
                </a>
                <div class="flex items-center gap-3">
                    <div class="avatar-lg">
                        <span>{{ $user->initials }}</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-brand-dark">{{ $user->name }}</h1>
                        <p class="text-sm text-surface-500">{{ $user->email }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            @if($user->team)
                                <span class="badge-info">{{ $user->team->name }}</span>
                            @endif
                            @if($user->careerLevels->isNotEmpty())
                                @foreach($user->careerLevels as $cl)
                                    <span class="badge-primary">{{ $cl->careerPath->name }} &ndash; {{ $cl->title }}</span>
                                @endforeach
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
            @foreach($suggestions as $suggestion)
                <div class="alert-info">
                    <div class="flex items-start gap-2">
                        <svg class="w-5 h-5 text-ui-info flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium">{{ $suggestion['message'] }}</p>
                            @if($suggestion['type'] === 'next_level')
                                <form method="POST" action="{{ route('manage.employees.assignCareerLevel', $user) }}" class="inline mt-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="career_level_id" value="{{ $suggestion['career_level_id'] }}">
                                    <button type="submit" class="btn-primary btn-sm">Auf n&auml;chste Stufe hochstufen</button>
                                </form>
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
        @endif

        {{-- Two-column layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left Column: Karrierepfad + Add Module --}}
            <div class="lg:col-span-1 space-y-6">

                {{-- Karrierepfad --}}
                <div class="card-tool">
                    <div class="card-tool-header">
                        <h3 class="card-tool-title">Karrierepfad</h3>
                    </div>
                    <div class="card-tool-body space-y-3">
                        @if($user->careerLevels->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($user->careerLevels as $cl)
                                <div class="flex items-center justify-between">
                                    <div>
                                        <span class="font-medium text-brand-dark">{{ $cl->careerPath->name }}</span>
                                        <span class="text-surface-500">&ndash; {{ $cl->title }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('manage.employees.removeCareerPath', $user) }}" class="inline"
                                          onsubmit="return confirm('Karrierepfad &quot;{{ $cl->careerPath->name }}&quot; wirklich entfernen?')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="career_level_id" value="{{ $cl->id }}">
                                        <button type="submit" class="btn-danger btn-xs">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </form>
                                </div>
                                @endforeach
                            </div>
                        @endif

                        <form method="POST" action="{{ route('manage.employees.assignCareerLevel', $user) }}">
                            @csrf
                            @method('PATCH')
                            <label class="input-label">Karrierepfad hinzuf&uuml;gen</label>
                            <select name="career_level_id" class="input-field w-full mb-2">
                                <option value="">Pfad &amp; Stufe w&auml;hlen&hellip;</option>
                                @foreach($careerPaths as $path)
                                    <optgroup label="{{ $path->name }}">
                                        @foreach($path->levels as $level)
                                            <option value="{{ $level->id }}" {{ $user->career_level_id === $level->id ? 'selected' : '' }}>
                                                {{ $level->title }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <button type="submit" class="btn-primary btn-sm w-full">Zuweisen</button>
                        </form>
                    </div>
                </div>

                {{-- Add Module with Filter --}}
                <div class="card-tool" x-data="moduleAssigner()">
                    <div class="card-tool-header">
                        <h3 class="card-tool-title">Modul hinzuf&uuml;gen</h3>
                    </div>
                    <div class="card-tool-body space-y-3">
                        <div>
                            <label class="input-label">Karrierepfad filtern</label>
                            <select x-model="filterPathId" @change="filterLevelId = ''" class="input-field w-full">
                                <option value="">Alle Pfade</option>
                                <template x-for="p in paths" :key="p.id">
                                    <option :value="p.id" x-text="p.name"></option>
                                </template>
                            </select>
                        </div>

                        <div x-show="filterPathId">
                            <label class="input-label">Level filtern</label>
                            <select x-model="filterLevelId" class="input-field w-full">
                                <option value="">Alle Levels</option>
                                <template x-for="l in filteredLevels" :key="l.id">
                                    <option :value="l.id" x-text="l.title"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="input-label">Modul ausw&auml;hlen</label>
                            <select x-model="selectedModuleId" class="input-field w-full">
                                <option value="">Modul w&auml;hlen&hellip;</option>
                                <template x-if="filteredGlobalModules.length > 0 && !filterPathId">
                                    <optgroup label="Allgemeine Module">
                                        <template x-for="m in filteredGlobalModules" :key="m.id">
                                            <option :value="m.id" x-text="m.title"></option>
                                        </template>
                                    </optgroup>
                                </template>
                                <template x-for="group in groupedFilteredModules" :key="group.label">
                                    <optgroup :label="group.label">
                                        <template x-for="m in group.modules" :key="m.id">
                                            <option :value="m.id" x-text="m.title"></option>
                                        </template>
                                    </optgroup>
                                </template>
                            </select>
                        </div>

                        <form method="POST" action="{{ route('manage.employees.assignModule', $user) }}">
                            @csrf
                            <input type="hidden" name="module_id" :value="selectedModuleId">
                            <button type="submit" class="btn-primary btn-sm w-full" :disabled="!selectedModuleId">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Zuweisen
                            </button>
                        </form>
                    </div>
                </div>

            </div>

            {{-- Right Column: Modules --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Active Modules --}}
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
                                                <a href="{{ route('trainer.schulungen.show', $module) }}" class="text-sm font-medium text-brand-dark truncate block hover:text-brand-primary transition-colors">{{ $module->title }}</a>
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
                                                    @if($enrollment?->trainingSession)
                                                        <span class="text-xs text-surface-300">&middot;</span>
                                                        <span class="text-xs text-surface-400">Termin {{ $enrollment->trainingSession->start_at->format('d.m.Y') }}</span>
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
                                                <form method="POST" action="{{ route('manage.employees.disableCareerModule', [$user, $module]) }}" class="inline"
                                                      onsubmit="return confirm('Modul \u0022{{ $module->title }}\u0022 f\u00fcr diesen Mitarbeiter deaktivieren?')">
                                                    @csrf
                                                    <button type="submit" class="btn-warning btn-xs" title="Standard-Modul deaktivieren">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                            @if($isDirectAssignment)
                                                <form method="POST" action="{{ route('manage.employees.removeModule', [$user, $module]) }}" class="inline"
                                                      onsubmit="return confirm('Modul-Zuweisung wirklich entfernen?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-danger btn-xs" title="Zuweisung entfernen">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Pending Interests --}}
                @if($pendingInterests->isNotEmpty())
                <div class="card-tool border-brand-accent border-t-4">
                    <div class="card-tool-header flex items-center justify-between">
                        <h3 class="card-tool-title">Schulungsinteressen ({{ $pendingInterests->count() }})</h3>
                        <span class="badge-accent text-xs">Offen</span>
                    </div>
                    <div class="card-tool-body p-0">
                        <div class="divide-y divide-surface-100">
                            @foreach($pendingInterests as $interest)
                                <div class="flex items-center justify-between px-4 py-3">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <svg class="w-5 h-5 text-brand-accent flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-brand-dark truncate">{{ $interest->module->title }}</div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                @if($interest->module->careerLevel?->careerPath)
                                                    <span class="text-xs text-surface-400">{{ $interest->module->careerLevel->careerPath->name }}</span>
                                                    <span class="text-xs text-surface-300">&middot;</span>
                                                @endif
                                                <span class="text-xs text-surface-400">Bekundet am {{ $interest->created_at->format('d.m.Y') }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                        @if($allModules->contains('id', $interest->module_id))
                                            <span class="badge-success text-xs">Bereits zugewiesen</span>
                                        @else
                                            <form method="POST" action="{{ route('manage.employees.assignFromInterest', [$user, $interest]) }}">
                                                @csrf
                                                <button type="submit" class="btn-accent btn-xs">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                    </svg>
                                                    Modul zuweisen
                                                </button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('manage.employees.noteInterest', [$user, $interest]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-outline btn-xs">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Zur Kenntnis
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Disabled Career Modules --}}
                @php
                    $disabledModules = $careerModules->filter(fn ($m) => in_array($m->id, $disabledModuleIds));
                @endphp
                @if($disabledModules->isNotEmpty())
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
                                        <form method="POST" action="{{ route('manage.employees.enableCareerModule', [$user, $module]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-primary btn-xs" title="Modul wieder aktivieren">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Aktivieren
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Active Milestones --}}
                @if($activeMilestones->isNotEmpty())
                <div class="card-tool border-t-4 border-brand-accent">
                    <div class="card-tool-header flex items-center justify-between">
                        <h3 class="card-tool-title">Milestones ({{ $activeMilestones->count() }})</h3>
                        <span class="badge-accent text-xs">On-the-job</span>
                    </div>
                    <div class="card-tool-body p-0">
                        <div class="divide-y divide-surface-100">
                            @foreach($activeMilestones->groupBy('category') as $category => $milestones)
                                <div class="px-4 py-2 bg-surface-50">
                                    <span class="text-xs font-semibold text-surface-500 uppercase tracking-wider">{{ \App\Models\Milestone::CATEGORIES[$category] ?? $category }}</span>
                                </div>
                                @foreach($milestones as $milestone)
                                <div class="flex items-center justify-between px-4 py-3">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $milestone->type === 'aktiv' ? 'bg-brand-accent' : 'bg-surface-300' }}"></div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-brand-dark truncate">{{ $milestone->title }}</div>
                                            <div class="flex items-center gap-1.5 mt-0.5">
                                                <span class="badge-{{ $milestone->type === 'aktiv' ? 'accent' : 'neutral' }} text-xs">{{ $milestone->typeLabel() }}</span>
                                                @if($milestone->team)
                                                <span class="text-xs text-surface-400">{{ $milestone->team->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                        <form method="POST" action="{{ route('manage.employees.disableMilestone', [$user, $milestone]) }}" class="inline"
                                              onsubmit="return confirm('Milestone für diesen Mitarbeiter deaktivieren?')">
                                            @csrf
                                            <button type="submit" class="btn-warning btn-xs" title="Milestone deaktivieren">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                {{-- Disabled Milestones --}}
                @if($disabledMilestonesList->isNotEmpty())
                <div class="card-tool border-surface-200 bg-surface-50/50">
                    <div class="card-tool-header flex items-center justify-between">
                        <h3 class="card-tool-title text-surface-400">Deaktivierte Milestones ({{ $disabledMilestonesList->count() }})</h3>
                    </div>
                    <div class="card-tool-body p-0">
                        <div class="divide-y divide-surface-100">
                            @foreach($disabledMilestonesList as $milestone)
                                <div class="flex items-center justify-between px-4 py-3 opacity-60">
                                    <div class="flex items-center gap-3 flex-1 min-w-0">
                                        <svg class="w-5 h-5 text-surface-300 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium text-surface-400 truncate line-through">{{ $milestone->title }}</div>
                                            <span class="text-xs text-surface-400">Deaktiviert</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0 ml-2">
                                        <form method="POST" action="{{ route('manage.employees.enableMilestone', [$user, $milestone]) }}" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-primary btn-xs" title="Milestone wieder aktivieren">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                Aktivieren
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>

    @push('scripts')
    @php
        $existingIdsJson = $allModules->pluck('id')->merge(collect($disabledModuleIds))->values();
        $pathsJson = $careerPaths->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'levels' => $p->levels->map(function ($l) {
                    return ['id' => $l->id, 'title' => $l->title];
                })->values(),
            ];
        })->values();
    @endphp
    <script>
        function moduleAssigner() {
            const allModules = @json($availableModulesJson);
            const existingIds = @json($existingIdsJson);
            const paths = @json($pathsJson);

            return {
                filterPathId: '',
                filterLevelId: '',
                selectedModuleId: '',
                paths,

                get availableModules() {
                    return allModules.filter(m => !existingIds.includes(m.id));
                },

                get filteredLevels() {
                    if (!this.filterPathId) return [];
                    const path = this.paths.find(p => p.id == this.filterPathId);
                    return path ? path.levels : [];
                },

                get filteredGlobalModules() {
                    return this.availableModules.filter(m => !m.career_level_id);
                },

                get filteredPathModules() {
                    let modules = this.availableModules.filter(m => m.career_level_id);
                    if (this.filterPathId) {
                        modules = modules.filter(m => m.path_id == this.filterPathId);
                    }
                    if (this.filterLevelId) {
                        modules = modules.filter(m => m.career_level_id == this.filterLevelId);
                    }
                    return modules;
                },

                get groupedFilteredModules() {
                    const groups = {};
                    this.filteredPathModules.forEach(m => {
                        const label = m.path_name + ' \u2013 ' + m.level_title;
                        if (!groups[label]) groups[label] = { label, modules: [] };
                        groups[label].modules.push(m);
                    });
                    return Object.values(groups);
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
