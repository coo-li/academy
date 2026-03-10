<x-app-layout>
    @section('page-title', 'Terminmanagement')

    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Terminmanagement</h1>
                <p class="text-surface-500 mt-1">Workshop-Termine für deine Schulungen verwalten.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- New Session Form --}}
            <div>
                <x-card title="Neuen Termin erstellen">
                    <form method="POST" action="{{ route('trainer.termine.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label class="label label-required">Modul</label>
                            <select name="module_id" class="select-field" required>
                                <option value="">Modul wählen...</option>
                                @foreach($modules as $module)
                                <option value="{{ $module->id }}">
                                    {{ $module->title }} ({{ $module->careerLevel?->title ?? 'Ohne Level' }})
                                </option>
                                @endforeach
                            </select>
                            @error('module_id') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label label-required">Start</label>
                            <input type="datetime-local" name="start_at" class="input-field" required value="{{ old('start_at') }}">
                            @error('start_at') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label label-required">Ende</label>
                            <input type="datetime-local" name="end_at" class="input-field" required value="{{ old('end_at') }}">
                            @error('end_at') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Ort</label>
                            <input type="text" name="location" class="input-field" placeholder="z.B. Raum 301 oder Google Meet Link" value="{{ old('location') }}">
                        </div>

                        <div>
                            <label class="label">Max. Teilnehmer</label>
                            <input type="number" name="max_participants" class="input-field" min="1" max="100" placeholder="15" value="{{ old('max_participants') }}">
                        </div>

                        <button type="submit" class="btn-primary w-full">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Termin erstellen
                        </button>
                    </form>
                </x-card>
            </div>

            {{-- Sessions List --}}
            <div class="lg:col-span-2">
                <x-card title="Terminübersicht">
                    @if($sessions->isNotEmpty())
                    <div class="overflow-x-auto -mx-4 sm:-mx-5">
                        <div class="divide-y divide-surface-200">
                            @foreach($sessions as $session)
                            <div class="py-4 first:pt-0 last:pb-0">
                                <div class="flex items-center justify-between gap-4">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-brand-dark">{{ $session->module->title }}</div>
                                        <div class="text-xs text-surface-500">{{ $session->module->methodLabel() }}</div>
                                    </div>
                                    <div class="flex-shrink-0 text-sm text-right">
                                        <div>{{ $session->start_at->format('d.m.Y') }}</div>
                                        <div class="text-xs text-surface-500">{{ $session->start_at->format('H:i') }} – {{ $session->end_at->format('H:i') }} Uhr</div>
                                    </div>
                                    @if($session->location)
                                    <div class="flex-shrink-0">
                                        <span class="text-xs text-surface-500">{{ $session->location }}</span>
                                    </div>
                                    @endif
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <span class="badge-info">{{ $session->enrollments->count() }}{{ $session->max_participants ? '/' . $session->max_participants : '' }}</span>
                                        <form method="POST" action="{{ route('trainer.termine.destroy', $session) }}" class="inline"
                                              onsubmit="return confirm('Termin wirklich löschen?')">
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
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-4">
                        {{ $sessions->links() }}
                    </div>
                    @else
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div class="empty-state-title">Keine Termine</div>
                        <div class="empty-state-description">Erstelle deinen ersten Workshop-Termin über das Formular links.</div>
                    </div>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
