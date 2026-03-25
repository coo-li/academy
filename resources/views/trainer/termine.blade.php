<x-app-layout>
    @section('page-title', $showAll ? 'Alle Termine' : 'Meine Termine')

    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('warning'))
            <x-alert type="warning" title="Hinweis" :dismissible="true">{{ session('warning') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">{{ $showAll ? 'Alle Termine' : 'Meine Termine' }}</h1>
                <p class="text-surface-500 mt-1">
                    {{ $showAll ? 'Alle Workshop-Termine im Überblick.' : 'Workshop-Termine für deine Schulungen verwalten.' }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- New Session Form --}}
            <div>
                <x-card title="Neuen Termin erstellen">
                    <form method="POST" action="{{ route('trainer.termine.store') }}" class="space-y-4" id="termin-form">
                        @csrf

                        <div>
                            <label class="label label-required">Modul</label>
                            <select name="module_id" class="select-field" required id="module-select">
                                <option value="">Modul wählen...</option>
                                @foreach($modules as $module)
                                <option value="{{ $module->id }}"
                                    {{ old('module_id') == $module->id ? 'selected' : '' }}>
                                    {{ $module->title }} ({{ $module->careerLevel?->title ?? 'Ohne Level' }})
                                    @if($showAll && $module->accountableUser) – {{ $module->accountableUser->name }} @endif
                                </option>
                                @endforeach
                            </select>
                            @error('module_id') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div id="trainer-wrapper" class="hidden">
                            <label class="label">Trainer</label>
                            <select name="trainer_id" class="select-field" id="trainer-select">
                                <option value="">Trainer wählen...</option>
                            </select>
                            <p class="text-xs text-surface-400 mt-1">Wer leitet diesen Termin? Kalendereinladung geht an den gewählten Trainer.</p>
                            @error('trainer_id') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label label-required">Startdatum</label>
                            <input type="date" name="start_date" class="input-field" required value="{{ old('start_date') }}">
                            @error('start_date') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label label-required">Startzeit</label>
                            <select name="start_time" class="select-field" required id="start-time">
                                <option value="">Uhrzeit wählen...</option>
                                @for($h = 7; $h <= 21; $h++)
                                    @foreach(['00', '15', '30', '45'] as $m)
                                        @php $t = sprintf('%02d:%s', $h, $m); @endphp
                                        <option value="{{ $t }}" {{ old('start_time') === $t ? 'selected' : '' }}>{{ $t }} Uhr</option>
                                    @endforeach
                                @endfor
                            </select>
                            @error('start_time') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label label-required">Enddatum</label>
                            <input type="date" name="end_date" class="input-field" required value="{{ old('end_date') }}">
                            @error('end_date') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label label-required">Endzeit</label>
                            <select name="end_time" class="select-field" required id="end-time">
                                <option value="">Uhrzeit wählen...</option>
                                @for($h = 7; $h <= 21; $h++)
                                    @foreach(['00', '15', '30', '45'] as $m)
                                        @php $t = sprintf('%02d:%s', $h, $m); @endphp
                                        <option value="{{ $t }}" {{ old('end_time') === $t ? 'selected' : '' }}>{{ $t }} Uhr</option>
                                    @endforeach
                                @endfor
                            </select>
                            @error('end_time') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="label">Raum / Ressource</label>
                            <select name="resource_email" class="select-field" id="resource-select">
                                <option value="">Kein Raum (Freitext nutzen)</option>
                                @foreach($resources as $resource)
                                <option value="{{ $resource['email'] }}" {{ old('resource_email') === $resource['email'] ? 'selected' : '' }}
                                    data-capacity="{{ $resource['capacity'] }}">
                                    {{ $resource['name'] }}
                                    @if($resource['capacity']) ({{ $resource['capacity'] }} Plätze) @endif
                                </option>
                                @endforeach
                            </select>
                            <div id="availability-indicator" class="mt-1 text-xs hidden"></div>
                            @error('resource_email') <p class="error-text">{{ $message }}</p> @enderror
                        </div>

                        <div id="location-wrapper">
                            <label class="label">Ort (Freitext)</label>
                            <input type="text" name="location" class="input-field" placeholder="z.B. externer Veranstaltungsort" value="{{ old('location') }}">
                            <p class="text-xs text-surface-400 mt-1">Nur nötig, wenn kein Raum aus der Liste gewählt wird.</p>
                        </div>

                        <div>
                            <label class="label">Max. Teilnehmer</label>
                            <input type="number" name="max_participants" class="input-field" min="1" max="100" placeholder="15" value="{{ old('max_participants') }}">
                        </div>

                        <div>
                            <label class="label">Kalenderbeschreibung</label>
                            <textarea name="calendar_description" class="input-field" rows="3" id="calendar-description" placeholder="Beschreibung für den Google Calendar Termin...">{{ old('calendar_description') }}</textarea>
                            <p class="text-xs text-surface-400 mt-1">Wird aus der Standard-Kalenderbeschreibung des Moduls vorausgefüllt.</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="hidden" name="google_meet" value="0">
                            <input type="checkbox" name="google_meet" value="1" id="google_meet" class="rounded border-surface-300 text-brand-primary focus:ring-brand-primary" {{ old('google_meet') ? 'checked' : '' }}>
                            <label for="google_meet" class="text-sm text-surface-600">Google Meet Link erstellen</label>
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
            <div class="lg:col-span-2 space-y-6">
                {{-- Upcoming Sessions --}}
                <x-card title="Kommende Termine">
                    @if($upcomingSessions->isNotEmpty())
                    <div class="divide-y divide-surface-200">
                        @foreach($upcomingSessions as $session)
                        <div class="py-3 first:pt-0 last:pb-0">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <a href="{{ route('trainer.schulungen.show', $session->module) }}" class="font-medium text-brand-dark truncate block hover:text-brand-primary transition-colors">{{ $session->module->title }}</a>
                                    <div class="text-xs text-surface-500 mt-0.5">
                                        {{ $session->module->methodLabel() }}
                                        @if($session->trainer)
                                            &middot; {{ $session->trainer->name }}
                                        @elseif($showAll && $session->module->accountableUser)
                                            &middot; {{ $session->module->accountableUser->name }}
                                        @endif
                                    </div>
                                    @if($session->location)
                                    <div class="text-xs text-surface-400 mt-0.5 flex items-center gap-1">
                                        <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        {{ $session->location }}
                                    </div>
                                    @endif
                                </div>

                                <div class="text-sm text-right flex-shrink-0">
                                    <div class="font-medium">{{ $session->start_at->format('d.m.Y') }}</div>
                                    <div class="text-xs text-surface-500">{{ $session->start_at->format('H:i') }} – {{ $session->end_at->format('H:i') }} Uhr</div>
                                </div>

                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <a href="{{ route('trainer.teilnehmer.index') }}" class="badge-info hover:bg-brand-primary hover:text-white transition-colors" title="Teilnehmermanagement öffnen">
                                        <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                        {{ $session->enrollments->count() }}{{ $session->max_participants ? '/' . $session->max_participants : '' }}
                                    </a>
                                    @if($session->google_event_id)
                                        <span class="badge-success" title="Mit Google Calendar synchronisiert">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('trainer.termine.sync-calendar', $session) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="btn-secondary btn-xs" title="Mit Google Calendar synchronisieren">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                            </button>
                                        </form>
                                    @endif
                                    <button type="button" class="btn-secondary btn-xs edit-session-btn" title="Termin bearbeiten"
                                        data-session-id="{{ $session->id }}"
                                        data-module-id="{{ $session->module_id }}"
                                        data-module-title="{{ $session->module->title }}"
                                        data-start-date="{{ $session->start_at->format('Y-m-d') }}"
                                        data-start-time="{{ $session->start_at->format('H:i') }}"
                                        data-end-date="{{ $session->end_at->format('Y-m-d') }}"
                                        data-end-time="{{ $session->end_at->format('H:i') }}"
                                        data-location="{{ $session->location }}"
                                        data-max-participants="{{ $session->max_participants }}"
                                        data-trainer-id="{{ $session->trainer_id }}"
                                        data-enrollment-count="{{ $session->enrollments->whereIn('status', ['enrolled', 'attended', 'completed'])->count() }}"
                                        data-has-calendar="{{ $session->google_event_id ? '1' : '0' }}"
                                        data-update-url="{{ route('trainer.termine.update', $session) }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
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
                    @else
                    <div class="empty-state">
                        <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <div class="empty-state-title">Keine kommenden Termine</div>
                        <div class="empty-state-description">Erstelle deinen ersten Workshop-Termin über das Formular links.</div>
                    </div>
                    @endif
                </x-card>

                {{-- Past Sessions --}}
                @if($pastSessions->isNotEmpty())
                <x-card>
                    <details>
                        <summary class="cursor-pointer select-none flex items-center gap-2 text-lg font-semibold text-brand-dark">
                            <svg class="w-4 h-4 transition-transform details-chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            Vergangene Termine
                            <span class="text-xs font-normal text-surface-400 ml-1">({{ $pastSessions->total() }})</span>
                        </summary>
                        <div class="mt-4 opacity-75">
                            <div class="divide-y divide-surface-200">
                                @foreach($pastSessions as $session)
                                <div class="py-3 first:pt-0 last:pb-0">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('trainer.schulungen.show', $session->module) }}" class="font-medium text-brand-dark truncate block hover:text-brand-primary transition-colors">{{ $session->module->title }}</a>
                                            <div class="text-xs text-surface-500 mt-0.5">
                                                {{ $session->module->methodLabel() }}
                                                @if($session->trainer)
                                                    &middot; {{ $session->trainer->name }}
                                                @elseif($showAll && $session->module->accountableUser)
                                                    &middot; {{ $session->module->accountableUser->name }}
                                                @endif
                                            </div>
                                            @if($session->location)
                                            <div class="text-xs text-surface-400 mt-0.5 flex items-center gap-1">
                                                <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                {{ $session->location }}
                                            </div>
                                            @endif
                                        </div>

                                        <div class="text-sm text-right flex-shrink-0">
                                            <div class="font-medium">{{ $session->start_at->format('d.m.Y') }}</div>
                                            <div class="text-xs text-surface-500">{{ $session->start_at->format('H:i') }} – {{ $session->end_at->format('H:i') }} Uhr</div>
                                        </div>

                                        <div class="flex items-center gap-1.5 flex-shrink-0">
                                            <a href="{{ route('trainer.teilnehmer.index') }}" class="badge-info hover:bg-brand-primary hover:text-white transition-colors" title="Teilnehmermanagement öffnen">
                                                <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                                {{ $session->enrollments->count() }}{{ $session->max_participants ? '/' . $session->max_participants : '' }}
                                            </a>
                                            @if($session->google_event_id)
                                                <span class="badge-success" title="Mit Google Calendar synchronisiert">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('trainer.termine.sync-calendar', $session) }}" class="inline">
                                                    @csrf
                                                    <button type="submit" class="btn-secondary btn-xs" title="Mit Google Calendar synchronisieren">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                    </button>
                                                </form>
                                            @endif
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

                            <div class="mt-4">
                                {{ $pastSessions->links() }}
                            </div>
                        </div>
                    </details>
                </x-card>
                @endif
            </div>
        </div>
    </div>

    {{-- Edit Session Modal --}}
    <div id="edit-modal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/50" id="edit-modal-backdrop"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg">
                    <div class="px-6 py-4 border-b border-surface-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-brand-dark">Termin bearbeiten</h3>
                            <p class="text-sm text-surface-500 mt-0.5" id="edit-modal-module-title"></p>
                        </div>
                        <button type="button" id="edit-modal-close" class="text-surface-400 hover:text-surface-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <form method="POST" id="edit-session-form" class="px-6 py-4 space-y-4">
                        @csrf
                        @method('PUT')

                        <div id="edit-enrollment-hint" class="hidden rounded-lg bg-blue-50 border border-blue-200 p-3 text-sm text-blue-800">
                            <div class="flex gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span id="edit-enrollment-text"></span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="label label-required">Startdatum</label>
                                <input type="date" name="start_date" class="input-field" required id="edit-start-date">
                            </div>
                            <div>
                                <label class="label label-required">Startzeit</label>
                                <select name="start_time" class="select-field" required id="edit-start-time">
                                    <option value="">Uhrzeit...</option>
                                    @for($h = 7; $h <= 21; $h++)
                                        @foreach(['00', '15', '30', '45'] as $m)
                                            @php $t = sprintf('%02d:%s', $h, $m); @endphp
                                            <option value="{{ $t }}">{{ $t }} Uhr</option>
                                        @endforeach
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="label label-required">Enddatum</label>
                                <input type="date" name="end_date" class="input-field" required id="edit-end-date">
                            </div>
                            <div>
                                <label class="label label-required">Endzeit</label>
                                <select name="end_time" class="select-field" required id="edit-end-time">
                                    <option value="">Uhrzeit...</option>
                                    @for($h = 7; $h <= 21; $h++)
                                        @foreach(['00', '15', '30', '45'] as $m)
                                            @php $t = sprintf('%02d:%s', $h, $m); @endphp
                                            <option value="{{ $t }}">{{ $t }} Uhr</option>
                                        @endforeach
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="label">Raum / Ressource</label>
                            <select name="resource_email" class="select-field" id="edit-resource-select">
                                <option value="">Kein Raum (Freitext nutzen)</option>
                                @foreach($resources as $resource)
                                <option value="{{ $resource['email'] }}" data-name="{{ $resource['name'] }}">
                                    {{ $resource['name'] }}
                                    @if($resource['capacity']) ({{ $resource['capacity'] }} Plätze) @endif
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div id="edit-location-wrapper">
                            <label class="label">Ort (Freitext)</label>
                            <input type="text" name="location" class="input-field" id="edit-location" placeholder="z.B. externer Veranstaltungsort">
                        </div>

                        <div>
                            <label class="label">Max. Teilnehmer</label>
                            <input type="number" name="max_participants" class="input-field" min="1" max="100" id="edit-max-participants">
                        </div>

                        <div id="edit-trainer-wrapper">
                            <label class="label">Trainer</label>
                            <select name="trainer_id" class="select-field" id="edit-trainer-select">
                                <option value="">Trainer wählen...</option>
                            </select>
                        </div>

                        <div>
                            <label class="label">Kalenderbeschreibung</label>
                            <textarea name="calendar_description" class="input-field" rows="2" id="edit-calendar-description" placeholder="Optionale Beschreibung für den Google Calendar Termin..."></textarea>
                        </div>

                        <div id="edit-calendar-hint" class="hidden rounded-lg bg-amber-50 border border-amber-200 p-3 text-xs text-amber-800">
                            <div class="flex gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>
                                <span>Bitte ändere Termine nicht direkt in deinem Google Calendar – solche Änderungen gelten nur für dich. Änderungen hier werden automatisch im Kalender aller Teilnehmenden aktualisiert.</span>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="btn-primary flex-1">Änderungen speichern</button>
                            <button type="button" id="edit-modal-cancel" class="btn-secondary flex-1">Abbrechen</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        details[open] > summary .details-chevron { transform: rotate(90deg); }
    </style>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const startDate = document.querySelector('[name="start_date"]');
        const startTime = document.getElementById('start-time');
        const endDate = document.querySelector('[name="end_date"]');
        const endTime = document.getElementById('end-time');
        const resourceSelect = document.getElementById('resource-select');
        const locationWrapper = document.getElementById('location-wrapper');
        const indicator = document.getElementById('availability-indicator');

        const moduleCalendarDescriptions = @js($modules->pluck('calendar_description', 'id'));
        const moduleTrainers = @js($moduleTrainers);
        const calendarDescTextarea = document.getElementById('calendar-description');
        const moduleSelect = document.getElementById('module-select');
        const trainerSelect = document.getElementById('trainer-select');
        const trainerWrapper = document.getElementById('trainer-wrapper');

        function updateTrainerDropdown() {
            if (!trainerSelect || !trainerWrapper) return;
            const moduleId = moduleSelect ? moduleSelect.value : '';
            const trainers = moduleId ? (moduleTrainers[moduleId] || []) : [];

            trainerSelect.innerHTML = '<option value="">Trainer wählen...</option>';
            trainers.forEach(function (t) {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                trainerSelect.appendChild(opt);
            });

            trainerWrapper.classList.toggle('hidden', trainers.length === 0);
        }

        if (moduleSelect) {
            moduleSelect.addEventListener('change', function () {
                const desc = moduleCalendarDescriptions[this.value] || '';
                if (calendarDescTextarea && !calendarDescTextarea.value.trim()) {
                    calendarDescTextarea.value = desc;
                }
                updateTrainerDropdown();
            });
            updateTrainerDropdown();
        }

        // Auto-copy start date to end date when start date changes
        if (startDate && endDate) {
            startDate.addEventListener('change', function () {
                if (!endDate.value) {
                    endDate.value = this.value;
                }
            });
        }

        // Auto-set end time to 1h after start time
        if (startTime && endTime) {
            startTime.addEventListener('change', function () {
                if (!endTime.value && this.value) {
                    const [h, m] = this.value.split(':').map(Number);
                    const endH = Math.min(h + 1, 21);
                    const candidate = String(endH).padStart(2, '0') + ':' + String(m).padStart(2, '0');
                    for (const opt of endTime.options) {
                        if (opt.value === candidate) {
                            endTime.value = candidate;
                            break;
                        }
                    }
                }
            });
        }

        // Toggle location field visibility based on resource selection
        function updateLocationVisibility() {
            if (!resourceSelect || !locationWrapper) return;
            locationWrapper.style.display = resourceSelect.value ? 'none' : 'block';
        }
        if (resourceSelect) {
            resourceSelect.addEventListener('change', function () {
                updateLocationVisibility();
                checkAvailability();
            });
            updateLocationVisibility();
        }

        // Check resource availability via AJAX
        let checkTimeout = null;
        function checkAvailability() {
            if (!resourceSelect || !indicator) return;
            if (!resourceSelect.value || !startDate.value || !startTime.value || !endDate.value || !endTime.value) {
                indicator.classList.add('hidden');
                return;
            }

            clearTimeout(checkTimeout);
            checkTimeout = setTimeout(function () {
                indicator.classList.remove('hidden');
                indicator.textContent = 'Verfügbarkeit wird geprüft...';
                indicator.className = 'mt-1 text-xs text-surface-500';

                fetch('{{ route("trainer.termine.check-availability") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        resource_email: resourceSelect.value,
                        start_date: startDate.value,
                        start_time: startTime.value,
                        end_date: endDate.value,
                        end_time: endTime.value,
                    }),
                })
                .then(r => r.json())
                .then(data => {
                    if (data.available === true) {
                        indicator.textContent = 'Raum ist verfügbar';
                        indicator.className = 'mt-1 text-xs text-green-600 font-medium';
                    } else if (data.available === false) {
                        indicator.textContent = 'Raum ist zu dieser Zeit belegt';
                        indicator.className = 'mt-1 text-xs text-red-600 font-medium';
                    } else {
                        indicator.textContent = 'Verfügbarkeit konnte nicht geprüft werden';
                        indicator.className = 'mt-1 text-xs text-surface-400';
                    }
                })
                .catch(() => {
                    indicator.textContent = 'Verfügbarkeitsprüfung fehlgeschlagen';
                    indicator.className = 'mt-1 text-xs text-surface-400';
                });
            }, 400);
        }

        [startDate, startTime, endDate, endTime].forEach(el => {
            if (el) el.addEventListener('change', checkAvailability);
        });

        // Restore trainer selection on validation error (old value)
        const oldTrainerId = '{{ old('trainer_id', '') }}';
        if (oldTrainerId && trainerSelect) {
            updateTrainerDropdown();
            trainerSelect.value = oldTrainerId;
        }

        // --- Edit Modal Logic ---
        const editModal = document.getElementById('edit-modal');
        const editForm = document.getElementById('edit-session-form');
        const editModalTitle = document.getElementById('edit-modal-module-title');
        const editStartDate = document.getElementById('edit-start-date');
        const editStartTime = document.getElementById('edit-start-time');
        const editEndDate = document.getElementById('edit-end-date');
        const editEndTime = document.getElementById('edit-end-time');
        const editResourceSelect = document.getElementById('edit-resource-select');
        const editLocationWrapper = document.getElementById('edit-location-wrapper');
        const editLocation = document.getElementById('edit-location');
        const editMaxParticipants = document.getElementById('edit-max-participants');
        const editTrainerSelect = document.getElementById('edit-trainer-select');
        const editTrainerWrapper = document.getElementById('edit-trainer-wrapper');
        const editCalendarDesc = document.getElementById('edit-calendar-description');
        const editEnrollmentHint = document.getElementById('edit-enrollment-hint');
        const editEnrollmentText = document.getElementById('edit-enrollment-text');
        const editCalendarHint = document.getElementById('edit-calendar-hint');

        function openEditModal(btn) {
            const d = btn.dataset;
            editForm.action = d.updateUrl;
            editModalTitle.textContent = d.moduleTitle;
            editStartDate.value = d.startDate;
            editStartTime.value = d.startTime;
            editEndDate.value = d.endDate;
            editEndTime.value = d.endTime;
            editMaxParticipants.value = d.maxParticipants || '';
            editCalendarDesc.value = '';

            // Resource / location
            const loc = d.location || '';
            let matched = false;
            for (const opt of editResourceSelect.options) {
                if (opt.dataset.name && opt.dataset.name === loc) {
                    editResourceSelect.value = opt.value;
                    matched = true;
                    break;
                }
            }
            if (!matched) {
                editResourceSelect.value = '';
                editLocation.value = loc;
            } else {
                editLocation.value = '';
            }
            editLocationWrapper.style.display = editResourceSelect.value ? 'none' : 'block';

            // Trainer dropdown
            const moduleId = d.moduleId;
            const trainers = moduleId ? (moduleTrainers[moduleId] || []) : [];
            editTrainerSelect.innerHTML = '<option value="">Trainer wählen...</option>';
            trainers.forEach(function (t) {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.name;
                if (String(t.id) === String(d.trainerId)) opt.selected = true;
                editTrainerSelect.appendChild(opt);
            });
            editTrainerWrapper.classList.toggle('hidden', trainers.length === 0);

            // Enrollment hint
            const count = parseInt(d.enrollmentCount, 10) || 0;
            if (count > 0) {
                editEnrollmentHint.classList.remove('hidden');
                editEnrollmentText.textContent = count + (count === 1 ? ' Teilnehmer eingebucht' : ' Teilnehmer eingebucht') + ' – Änderungen an Datum/Zeit/Ort werden automatisch im Google Calendar aller Teilnehmenden aktualisiert.';
                editMaxParticipants.min = count;
            } else {
                editEnrollmentHint.classList.add('hidden');
                editMaxParticipants.min = 1;
            }

            // Calendar hint
            editCalendarHint.classList.toggle('hidden', d.hasCalendar !== '1');

            editModal.classList.remove('hidden');
        }

        function closeEditModal() {
            editModal.classList.add('hidden');
        }

        document.querySelectorAll('.edit-session-btn').forEach(function (btn) {
            btn.addEventListener('click', function () { openEditModal(this); });
        });
        document.getElementById('edit-modal-close').addEventListener('click', closeEditModal);
        document.getElementById('edit-modal-cancel').addEventListener('click', closeEditModal);
        document.getElementById('edit-modal-backdrop').addEventListener('click', closeEditModal);

        if (editResourceSelect) {
            editResourceSelect.addEventListener('change', function () {
                editLocationWrapper.style.display = this.value ? 'none' : 'block';
            });
        }
    });
    </script>
    @endpush
</x-app-layout>
