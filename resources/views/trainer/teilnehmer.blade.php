<x-app-layout>
    @section('page-title', 'Teilnehmermanagement')

    <div class="space-y-6">
        @if(session('success'))
            <x-alert type="success" title="Erfolg!" :dismissible="true">{{ session('success') }}</x-alert>
        @endif
        @if(session('error'))
            <x-alert type="error" title="Fehler" :dismissible="true">{{ session('error') }}</x-alert>
        @endif

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-brand-dark">Teilnehmermanagement</h1>
                <p class="text-surface-500 mt-1">Anwesenheit deiner Schulungsteilnehmer bestätigen.</p>
            </div>
        </div>

        @if($sessions->isNotEmpty())
        <div class="space-y-4">
            @foreach($sessions as $session)
            @php
                $enrolledUsers = $session->enrollments->where('status', 'enrolled');
                $attendedUsers = $session->enrollments->whereIn('status', ['attended', 'completed']);
            @endphp
            <div x-data="{ open: false, selectedAttendees: [] }" class="card-tool">
                <div class="card-tool-body">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-brand-dark">{{ $session->module->title }}</div>
                            <div class="text-xs text-surface-500">
                                {{ $session->start_at->format('d.m.Y, H:i') }} – {{ $session->end_at->format('H:i') }} Uhr
                                @if($session->location)
                                    &middot; {{ $session->location }}
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-3 flex-shrink-0">
                            @if($enrolledUsers->isNotEmpty())
                                <span class="badge-warning text-xs">{{ $enrolledUsers->count() }} offen</span>
                            @endif
                            @if($attendedUsers->isNotEmpty())
                                <span class="badge-success text-xs">{{ $attendedUsers->count() }} bestätigt</span>
                            @endif
                            <button @click="open = !open" class="btn-secondary btn-xs">
                                <span x-text="open ? 'Zuklappen' : 'Teilnehmer'"></span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div x-show="open" x-cloak x-transition class="mt-4 border border-surface-200 rounded-lg overflow-hidden">
                        @if($session->enrollments->isEmpty())
                            <div class="p-4 text-sm text-surface-500 text-center">Noch keine Teilnehmer eingeschrieben.</div>
                        @else
                            <form method="POST" action="{{ route('trainer.teilnehmer.confirm', $session) }}">
                                @csrf
                                <table class="table-tool">
                                    <thead>
                                        <tr>
                                            <th class="w-10"></th>
                                            <th>Teilnehmer</th>
                                            <th>Status</th>
                                            <th>Bestätigt am</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($session->enrollments->sortBy('user.name') as $enrollment)
                                        <tr>
                                            <td>
                                                @if($enrollment->status === 'enrolled')
                                                <input type="checkbox" name="attendees[]" value="{{ $enrollment->user_id }}"
                                                       class="checkbox-field" x-model="selectedAttendees">
                                                @else
                                                <svg class="w-5 h-5 text-ui-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="font-medium text-brand-dark">{{ $enrollment->user->name }}</div>
                                                <div class="text-xs text-surface-500">{{ $enrollment->user->email }}</div>
                                            </td>
                                            <td>
                                                @if($enrollment->status === 'enrolled')
                                                    <span class="badge-primary">Gebucht</span>
                                                @elseif($enrollment->status === 'attended')
                                                    <span class="badge-warning">Teilgenommen</span>
                                                @elseif($enrollment->status === 'completed')
                                                    <span class="badge-success">Abgeschlossen</span>
                                                @elseif($enrollment->status === 'cancelled')
                                                    <span class="badge-error">Storniert</span>
                                                @endif
                                            </td>
                                            <td class="text-xs text-surface-500">
                                                @if($enrollment->attendance_confirmed_at)
                                                    {{ $enrollment->attendance_confirmed_at->format('d.m.Y, H:i') }}
                                                @else
                                                    &ndash;
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                @if($enrolledUsers->isNotEmpty())
                                <div class="px-4 py-3 bg-surface-50 border-t border-surface-200 flex items-center justify-between">
                                    <span class="text-xs text-surface-500" x-text="selectedAttendees.length + ' Teilnehmer ausgewählt'"></span>
                                    <button type="submit" class="btn-primary btn-sm" :disabled="selectedAttendees.length === 0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Anwesenheit bestätigen
                                    </button>
                                </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4">
            {{ $sessions->links() }}
        </div>
        @else
        <div class="card-tool">
            <div class="card-tool-body">
                <div class="empty-state">
                    <svg class="empty-state-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <div class="empty-state-title">Keine Termine</div>
                    <div class="empty-state-description">Für deine Schulungen gibt es noch keine Termine mit Teilnehmern.</div>
                </div>
            </div>
        </div>
        @endif
    </div>
</x-app-layout>
