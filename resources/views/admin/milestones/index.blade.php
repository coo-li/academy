<x-app-layout>
    @section('page-title', 'Milestones verwalten')

    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif

        {{-- Page Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Milestones</h1>
                <p class="text-surface-500 mt-1">On-the-job Anforderungen pro Team und Karrierestufe verwalten.</p>
            </div>
            <a href="{{ route('admin.milestones.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Milestone erstellen
            </a>
        </div>

        {{-- Team Tabs --}}
        <div class="flex items-center gap-2 flex-wrap">
            @foreach($teams as $team)
                <a href="{{ route('admin.milestones.index', ['team' => $team->id]) }}"
                   class="btn-{{ $selectedTeamId == $team->id ? 'primary' : 'secondary' }} btn-sm">
                    {{ $team->name }}
                    @if(isset($levelCounts) && $selectedTeamId == $team->id)
                        <span class="ml-1 opacity-70">({{ $levelCounts->sum() }})</span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Milestones by Career Level --}}
        @if($selectedTeamId)
            @if($milestonesByLevel->isNotEmpty())
                <div class="space-y-6" x-data="{ openLevel: '{{ $milestonesByLevel->keys()->first() }}' }">
                    @foreach($milestonesByLevel as $levelId => $milestones)
                        @php $level = $milestones->first()->careerLevel; @endphp
                        <div class="bg-white rounded-2xl border border-surface-200 overflow-hidden">
                            <button type="button"
                                    @click="openLevel = openLevel === '{{ $levelId }}' ? null : '{{ $levelId }}'"
                                    class="w-full px-5 py-4 flex items-center justify-between cursor-pointer hover:bg-surface-50 transition-colors">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-brand-primary-light text-brand-primary flex items-center justify-center text-sm font-bold shrink-0">
                                        {{ $level->level_number }}
                                    </div>
                                    <div class="text-left">
                                        <h3 class="font-semibold text-brand-dark">
                                            {{ $level->careerPath->emoji ?? '📋' }}
                                            <a href="{{ route('admin.paths.show', $level->careerPath) }}"
                                               @click.stop
                                               class="hover:text-brand-primary transition-colors">{{ $level->careerPath->name }}</a>
                                            &ndash; {{ $level->title }}
                                        </h3>
                                        <span class="text-xs text-surface-400">{{ $milestones->count() }} Milestones</span>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 text-surface-400 transition-transform duration-200"
                                     :class="openLevel === '{{ $levelId }}' ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="openLevel === '{{ $levelId }}'"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 -translate-y-1"
                                 x-transition:enter-end="opacity-100 translate-y-0"
                                 x-cloak>
                                <div class="divide-y divide-surface-100">
                                    @foreach($milestones as $milestone)
                                    <div class="flex items-center justify-between px-5 py-3">
                                        <div class="flex items-center gap-3 flex-1 min-w-0">
                                            <div class="w-2 h-2 rounded-full flex-shrink-0 {{ $milestone->type === 'aktiv' ? 'bg-brand-accent' : 'bg-surface-300' }}"></div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm font-medium text-brand-dark">{{ $milestone->title }}</div>
                                                @if($milestone->description)
                                                <div class="text-xs text-surface-500 mt-0.5 line-clamp-2">{{ $milestone->description }}</div>
                                                @endif
                                                <div class="flex items-center gap-1.5 mt-1">
                                                    <span class="badge-{{ $milestone->type === 'aktiv' ? 'accent' : 'neutral' }} text-xs">{{ $milestone->typeLabel() }}</span>
                                                    @if($milestone->team)
                                                    <span class="badge-info text-xs">{{ $milestone->team->name }}</span>
                                                    @else
                                                    <span class="text-xs text-surface-400">Alle Teams</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 flex-shrink-0 ml-3">
                                            <a href="{{ route('admin.milestones.edit', $milestone) }}" class="btn-secondary btn-xs">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            <form method="POST" action="{{ route('admin.milestones.destroy', $milestone) }}" class="inline"
                                                  onsubmit="return confirm('Milestone wirklich löschen?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-danger btn-xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-white rounded-2xl border border-dashed border-surface-300 p-8 text-center">
                    <p class="text-surface-500 mb-3">Keine Milestones für dieses Team gefunden.</p>
                    <a href="{{ route('admin.milestones.create') }}" class="btn-primary btn-sm">Milestone erstellen</a>
                </div>
            @endif
        @else
            {{-- No team selected: show prompt --}}
            <div class="bg-white rounded-2xl border border-dashed border-surface-300 p-10 text-center">
                <svg class="w-12 h-12 mx-auto text-surface-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <p class="text-lg font-semibold text-brand-dark mb-1">Team auswählen</p>
                <p class="text-surface-500">Wähle oben ein Team, um dessen Milestones nach Karrierestufe zu sehen.</p>
            </div>
        @endif
    </div>
</x-app-layout>
