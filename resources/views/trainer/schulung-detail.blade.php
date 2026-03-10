<x-app-layout>
    @section('page-title', $module->title)

    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <a href="{{ route('trainer.schulungen.index') }}" class="text-sm text-surface-500 hover:text-brand-primary mb-1 inline-flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Zurück zur Übersicht
                </a>
                <h1 class="text-3xl font-bold text-brand-dark">{{ $module->title }}</h1>
                <div class="flex flex-wrap gap-2 mt-2">
                    @if($module->skillCategory)
                        <span class="badge-neutral">{{ $module->skillCategory->name }}</span>
                    @endif
                    @if($module->method)
                        <span class="badge-info">{{ $module->method->name }}</span>
                    @endif
                    @if($module->is_mandatory)
                        <span class="badge-error">Pflicht</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Description --}}
            <x-card title="Beschreibung bearbeiten">
                <form method="POST" action="{{ route('trainer.schulungen.update', $module) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <textarea name="description" rows="6" class="input-field w-full" placeholder="Beschreibung der Schulung...">{{ old('description', $module->description) }}</textarea>
                        @error('description') <p class="error-text">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn-primary">
                        Beschreibung speichern
                    </button>
                </form>
            </x-card>

            {{-- Materials Upload --}}
            <x-card title="Schulungsunterlagen">
                <form method="POST" action="{{ route('trainer.schulungen.materials.store', $module) }}" enctype="multipart/form-data" class="mb-4">
                    @csrf
                    <div class="flex items-end gap-3">
                        <div class="flex-1">
                            <label class="label label-required">Datei hochladen</label>
                            <input type="file" name="file" class="input-field" required
                                   accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.ppt,.pptx,.xls,.xlsx">
                            <p class="text-xs text-surface-400 mt-1">Max. 20 MB. PDF, Bilder, Office-Dokumente.</p>
                        </div>
                        <button type="submit" class="btn-primary btn-sm flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Hochladen
                        </button>
                    </div>
                    @error('file') <p class="error-text mt-1">{{ $message }}</p> @enderror
                </form>

                @if($module->trainingMaterials->isNotEmpty())
                <div class="divide-y divide-surface-200">
                    @foreach($module->trainingMaterials as $material)
                    <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex-shrink-0">
                                @if(str_starts_with($material->mime_type, 'image/'))
                                    <svg class="w-8 h-8 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                @else
                                    <svg class="w-8 h-8 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-brand-dark truncate">{{ $material->original_filename }}</div>
                                <div class="text-xs text-surface-500">
                                    {{ number_format($material->file_size / 1024, 0) }} KB &middot;
                                    {{ $material->created_at->format('d.m.Y, H:i') }}
                                    @if($material->uploader)
                                        &middot; {{ $material->uploader->name }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('trainer.schulungen.materials.destroy', $material) }}"
                              onsubmit="return confirm('Unterlage wirklich löschen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-xs">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-sm text-surface-500 text-center py-4">
                    Noch keine Unterlagen hochgeladen.
                </div>
                @endif
            </x-card>
        </div>

        {{-- Training Sessions --}}
        <x-card title="Termine für dieses Modul">
            @if($module->trainingSessions->isNotEmpty())
            <div class="divide-y divide-surface-200">
                @foreach($module->trainingSessions->sortByDesc('start_at') as $session)
                <div class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4">
                    <div>
                        <div class="font-medium text-brand-dark">{{ $session->start_at->format('d.m.Y') }}</div>
                        <div class="text-xs text-surface-500">{{ $session->start_at->format('H:i') }} – {{ $session->end_at->format('H:i') }} Uhr</div>
                    </div>
                    @if($session->location)
                        <span class="text-xs text-surface-500">{{ $session->location }}</span>
                    @endif
                    <span class="badge-info">{{ $session->enrollments->count() }} Teilnehmer</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-sm text-surface-500 text-center py-4">
                Noch keine Termine für dieses Modul angelegt.
            </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
