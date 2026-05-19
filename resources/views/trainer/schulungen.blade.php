<x-app-layout>
    @section('page-title', 'Meine Schulungsinhalte')

    <div class="space-y-6" x-data="schulungManager()">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-extrabold text-brand-dark tracking-tight">Meine Schulungsinhalte</h1>
                <p class="text-surface-500 mt-1">Inhalte und Unterlagen deiner zugewiesenen Schulungen verwalten.</p>
            </div>
            <div class="flex items-center gap-2 text-sm text-surface-500">
                <span class="badge-info" x-text="filteredModules.length + ' / ' + modules.length + ' Module'"></span>
            </div>
        </div>

        @if($modules->isNotEmpty())
        {{-- Filter Bar --}}
        <div class="card-tool">
            <div class="card-tool-body">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <input type="text"
                               x-model="search"
                               placeholder="Modul suchen&hellip;"
                               class="input-field w-full">
                    </div>
                    @if($isAdmin)
                    <div class="w-full sm:w-48">
                        <select x-model="trainerFilter" class="input-field w-full">
                            <option value="">Alle Trainer</option>
                            <template x-for="t in trainerOptions" :key="t">
                                <option :value="t" x-text="t"></option>
                            </template>
                        </select>
                    </div>
                    @endif
                    <div class="w-full sm:w-48">
                        <select x-model="pathFilter" class="input-field w-full">
                            <option value="">Alle Karrierepfade</option>
                            <template x-for="p in pathOptions" :key="p">
                                <option :value="p" x-text="p"></option>
                            </template>
                        </select>
                    </div>
                    <div class="w-full sm:w-48">
                        <select x-model="methodFilter" class="input-field w-full">
                            <option value="">Alle Methoden</option>
                            <template x-for="m in methodOptions" :key="m">
                                <option :value="m" x-text="m"></option>
                            </template>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grouped Module Cards --}}
        <template x-for="group in groupedModules" :key="group.name">
            <div class="space-y-4">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center text-xl shrink-0"
                         :class="group.type === 'path' ? 'bg-brand-primary-light' : group.type === 'skill' ? 'bg-surface-100' : 'bg-surface-50'"
                         x-text="group.emoji"></div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <template x-if="group.type === 'path' && group.pathId">
                            <a :href="'/admin/paths/' + group.pathId"
                               class="text-xl font-bold text-brand-dark hover:text-brand-primary transition-colors"
                               x-text="group.name"></a>
                        </template>
                        <template x-if="!(group.type === 'path' && group.pathId)">
                            <h2 class="text-xl font-bold text-brand-dark" x-text="group.name"></h2>
                        </template>
                        <template x-if="group.type === 'path'">
                            <span class="badge-info text-xs">Sequenziell</span>
                        </template>
                        <template x-if="group.type === 'skill'">
                            <span class="badge-neutral text-xs">Flexibel</span>
                        </template>
                        <span class="text-xs text-surface-400" x-text="group.modules.length + (group.modules.length === 1 ? ' Modul' : ' Module')"></span>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                    <template x-for="mod in group.modules" :key="mod.id">
                        <a :href="mod.url" class="card-tool hover:shadow-lg transition-shadow">
                            <div class="card-tool-body">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-brand-dark truncate" x-text="mod.title"></h3>
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            <template x-if="mod.level">
                                                <span class="badge-warning text-xs" x-text="mod.level"></span>
                                            </template>
                                            <template x-if="mod.skillGroup">
                                                <span class="badge-neutral text-xs" x-text="mod.skillGroup"></span>
                                            </template>
                                            <template x-if="mod.method">
                                                <span class="badge-primary text-xs" x-text="mod.method"></span>
                                            </template>
                                            @if($isAdmin)
                                            <template x-if="mod.trainer">
                                                <span class="badge-success text-xs" x-text="mod.trainer"></span>
                                            </template>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 mt-4 text-xs text-surface-500">
                                    <span x-text="mod.sessions + ' Termine'"></span>
                                    <span x-text="mod.enrollments + ' Einschreibungen'"></span>
                                    <span x-text="mod.materials + ' Unterlagen'"></span>
                                </div>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </template>

        {{-- No Results --}}
        <template x-if="filteredModules.length === 0">
            <div class="card-tool">
                <div class="card-tool-body">
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <div class="empty-state-title">Keine Treffer</div>
                        <div class="empty-state-description">Kein Modul entspricht den gewählten Filtern.</div>
                        <button @click="search = ''; trainerFilter = ''; pathFilter = ''; methodFilter = ''" class="btn-secondary btn-sm mt-3">Filter zurücksetzen</button>
                    </div>
                </div>
            </div>
        </template>

        @else
        <div class="card-tool">
            <div class="card-tool-body">
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <div class="empty-state-title">Keine Schulungen zugewiesen</div>
                    <div class="empty-state-description">Dir wurden noch keine Schulungsmodule als Trainer zugewiesen.</div>
                </div>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
        function schulungManager() {
            return {
                search: '',
                trainerFilter: '',
                pathFilter: '',
                methodFilter: '',

                modules: @json($modulesJson ?? []),

                get trainerOptions() {
                    return [...new Set(this.modules.map(m => m.trainer).filter(Boolean))].sort();
                },

                get pathOptions() {
                    return [...new Set(this.modules.map(m => m.path).filter(Boolean))].sort();
                },

                get methodOptions() {
                    return [...new Set(this.modules.map(m => m.method).filter(Boolean))].sort();
                },

                get filteredModules() {
                    let result = this.modules;

                    if (this.search) {
                        const q = this.search.toLowerCase();
                        result = result.filter(m =>
                            m.title.toLowerCase().includes(q) ||
                            m.trainer.toLowerCase().includes(q)
                        );
                    }

                    if (this.trainerFilter) {
                        result = result.filter(m => m.trainer === this.trainerFilter);
                    }

                    if (this.pathFilter) {
                        result = result.filter(m => m.path === this.pathFilter);
                    }

                    if (this.methodFilter) {
                        result = result.filter(m => m.method === this.methodFilter);
                    }

                    return result;
                },

                get groupedModules() {
                    const groups = {};
                    const meta = {};
                    const order = [];

                    this.filteredModules.forEach(m => {
                        let key, type, emoji;
                        if (m.path) {
                            key = m.path;
                            type = 'path';
                            emoji = m.pathEmoji || '\u{1F4CB}';
                        } else if (m.skillGroup) {
                            key = m.skillGroup;
                            type = 'skill';
                            emoji = m.skillGroupEmoji || '\u{1F9E9}';
                        } else {
                            key = 'Nicht zugeordnet';
                            type = 'none';
                            emoji = '\u{1F4E6}';
                        }

                        if (!groups[key]) {
                            groups[key] = [];
                            meta[key] = { type, emoji, pathId: m.pathId || null };
                            order.push(key);
                        }
                        groups[key].push(m);
                    });

                    return order.map(name => ({
                        name,
                        type: meta[name].type,
                        emoji: meta[name].emoji,
                        pathId: meta[name].pathId,
                        modules: groups[name],
                    }));
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
