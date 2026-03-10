<x-app-layout>
    @section('page-title', 'Schulungsdurchführung')

    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Schulungsdurchführung</h1>
                <p class="text-surface-500 mt-1">Inhalte und Unterlagen deiner zugewiesenen Schulungen verwalten.</p>
            </div>
        </div>

        @if($modules->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @foreach($modules as $module)
            <a href="{{ route('trainer.schulungen.show', $module) }}" class="card-tool hover:shadow-lg transition-shadow">
                <div class="card-tool-body">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold text-brand-dark truncate">{{ $module->title }}</h3>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                @if($module->careerLevel?->careerPath)
                                    <span class="badge-primary text-xs">{{ $module->careerLevel->careerPath->name }}</span>
                                @endif
                                @if($module->careerLevel)
                                    <span class="badge-warning text-xs">{{ $module->careerLevel->title }}</span>
                                @endif
                                @if($module->skillCategory)
                                    <span class="badge-neutral text-xs">{{ $module->skillCategory->name }}</span>
                                @endif
                                @if($module->method)
                                    <span class="badge-info text-xs">{{ $module->method->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 mt-4 text-xs text-surface-500">
                        <span>{{ $module->training_sessions_count }} Termine</span>
                        <span>{{ $module->enrollments_count }} Einschreibungen</span>
                        <span>{{ $module->trainingMaterials->count() }} Unterlagen</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
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
</x-app-layout>
