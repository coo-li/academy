<x-app-layout>
    @section('page-title', 'Schulungskatalog')

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
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Schulungskatalog</h1>
                <p class="text-surface-500 mt-1.5 text-base">Entdecke alle verf&uuml;gbaren Schulungen und bekunde dein Interesse.</p>
            </div>
            <div class="flex items-center gap-2 text-sm text-surface-500">
                <svg class="w-5 h-5 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <span class="font-semibold text-brand-dark">{{ $modules->total() }}</span> Module verf&uuml;gbar
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="card-tool">
            <div class="card-tool-body">
                <form method="GET" action="{{ route('academy.skill-overview') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <label class="input-label">Suche</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Modul suchen&hellip;" class="input-field w-full">
                    </div>
                    <div>
                        <label class="input-label">Karrierepfad</label>
                        <select name="career_path" class="input-field w-full">
                            <option value="">Alle Pfade</option>
                            @foreach($careerPaths as $path)
                                <option value="{{ $path->id }}" {{ request('career_path') == $path->id ? 'selected' : '' }}>
                                    {{ $path->emoji ? $path->emoji . ' ' : '' }}{{ $path->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="input-label">Skill-Kategorie</label>
                        <select name="skill_category" class="input-field w-full">
                            <option value="">Alle Kategorien</option>
                            @foreach($skillCategories as $cat)
                                <option value="{{ $cat->id }}" {{ request('skill_category') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="input-label">Methode</label>
                        <select name="method" class="input-field w-full">
                            <option value="">Alle Methoden</option>
                            @foreach($methods as $method)
                                <option value="{{ $method->id }}" {{ request('method') == $method->id ? 'selected' : '' }}>
                                    {{ $method->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn-primary btn-sm flex-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            Filtern
                        </button>
                        @if(request()->hasAny(['search', 'career_path', 'skill_category', 'method']))
                            <a href="{{ route('academy.skill-overview') }}" class="btn-secondary btn-sm">Zur&uuml;cksetzen</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Module Grid --}}
        @if($modules->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($modules as $module)
                @php
                    $enrollment = $enrollmentsByModule->get($module->id);
                    $interest = $interestsByModule->get($module->id);
                    $status = $enrollment?->status;

                    $pathEmoji = $module->careerLevel?->careerPath?->emoji;
                    $pathName = $module->careerLevel?->careerPath?->name;
                    $levelTitle = $module->careerLevel?->title;
                    $nextSession = $module->trainingSessions->first();
                @endphp

                <div class="card-tool opacity-0 animate-card-enter border-t-4 {{ match($status) {
                    'completed' => 'border-ui-success',
                    'attended' => 'border-brand-accent',
                    'enrolled' => 'border-brand-primary',
                    default => $interest ? 'border-brand-accent' : ($allAssignedModuleIds->contains($module->id) ? 'border-brand-primary' : 'border-surface-200'),
                } }}" style="animation-delay: {{ $loop->index * 50 }}ms">
                    <div class="card-tool-body">
                        {{-- Header: Path + Badges --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex items-center gap-2">
                                @if($pathEmoji)
                                    <span class="text-xl leading-none">{{ $pathEmoji }}</span>
                                @endif
                                @if($pathName && $module->careerLevel?->careerPath)
                                    <a href="{{ route('admin.paths.show', $module->careerLevel->careerPath) }}" class="badge-primary text-xs hover:opacity-80 transition-opacity">{{ $pathName }}</a>
                                @elseif($pathName)
                                    <span class="badge-primary text-xs">{{ $pathName }}</span>
                                @else
                                    <span class="badge-neutral text-xs">Allgemein</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5">
                                @if($module->method)
                                    <span class="badge-info text-xs">{{ $module->method->name }}</span>
                                @endif
                                @if($module->is_mandatory)
                                    <span class="badge-accent text-xs">Pflicht</span>
                                @endif
                            </div>
                        </div>

                        {{-- Title + Description --}}
                        <h3 class="font-bold font-display text-brand-dark text-lg mb-1">{{ $module->title }}</h3>
                        @if($levelTitle && $module->careerLevel?->careerPath)
                            <a href="{{ route('admin.paths.show', $module->careerLevel->careerPath) }}" class="text-xs text-surface-400 mb-2 block hover:text-brand-primary transition-colors">{{ $levelTitle }}</a>
                        @elseif($levelTitle)
                            <p class="text-xs text-surface-400 mb-2">{{ $levelTitle }}</p>
                        @endif
                        @if($module->description)
                            <div x-data="{ expanded: false }">
                                <p class="text-sm text-surface-500 leading-relaxed" :class="expanded ? '' : 'line-clamp-2'" x-cloak>{{ $module->description }}</p>
                                @if(mb_strlen($module->description) > 120)
                                <button @click="expanded = !expanded" class="text-xs text-brand-primary hover:text-brand-primary-hover font-medium mt-1 focus:outline-none" x-text="expanded ? 'Weniger anzeigen' : 'Mehr lesen'"></button>
                                @endif
                            </div>
                            <div class="mb-4"></div>
                        @endif

                        {{-- Skill Category --}}
                        @if($module->skillCategory)
                            <div class="mb-3">
                                <span class="badge-neutral text-xs">{{ $module->skillCategory->name }}</span>
                            </div>
                        @endif

                        {{-- Next Session --}}
                        @if($nextSession)
                            <div class="flex items-center gap-2 text-xs text-surface-500 bg-surface-50 rounded-lg px-3 py-2 mb-3">
                                <svg class="w-3.5 h-3.5 text-brand-primary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <span class="font-medium">N&auml;chster Termin: {{ $nextSession->start_at->format('d.m.Y, H:i') }} Uhr</span>
                                @if($nextSession->location)
                                    <span class="text-surface-300">&middot;</span>
                                    <span>{{ $nextSession->location }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Footer: Status + Action --}}
                    <div class="card-tool-footer">
                        <div class="flex items-center justify-between">
                            @if($status === 'completed')
                                <span class="badge-success">Abgeschlossen</span>
                                <a href="{{ route('academy.module.show', $module) }}" class="btn-secondary btn-xs">Details</a>
                            @elseif($status === 'attended')
                                <span class="badge-accent">Quiz offen</span>
                                <a href="{{ route('academy.module.show', $module) }}" class="btn-primary btn-xs">Zum Quiz</a>
                            @elseif($status === 'enrolled')
                                <span class="badge-primary">Gebucht</span>
                                <a href="{{ route('academy.module.show', $module) }}" class="btn-secondary btn-xs">Details</a>
                            @elseif($interest)
                                <span class="badge-accent">Interesse bekundet</span>
                                <form method="POST" action="{{ route('academy.interest.destroy', $module) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-secondary btn-xs">Zur&uuml;ckziehen</button>
                                </form>
                            @elseif($allAssignedModuleIds->contains($module->id))
                                <span class="badge-primary">Bereits zugewiesen</span>
                                <a href="{{ route('academy.module.show', $module) }}" class="btn-secondary btn-xs">Details</a>
                            @else
                                <span class="text-xs text-surface-400">Noch nicht eingeschrieben</span>
                                <form method="POST" action="{{ route('academy.interest.store', $module) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-primary btn-xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                        </svg>
                                        Interesse bekunden
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($modules->hasPages())
            <div class="mt-6 pb-4">
                {{ $modules->links() }}
            </div>
        @endif

        @else
            <div class="card-tool">
                <div class="card-tool-body">
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <div class="empty-state-title">Keine Module gefunden</div>
                        <div class="empty-state-description">Passe deine Filter an oder setze die Suche zur&uuml;ck.</div>
                        <a href="{{ route('academy.skill-overview') }}" class="btn-primary btn-sm mt-4">Filter zur&uuml;cksetzen</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
