<aside class="w-64 bg-gradient-to-b from-slate-800 to-slate-900 text-white flex flex-col" x-data="{ 
    meineEntwicklungOpen: {{ request()->routeIs('user.budget-status*', 'learning.*') ? 'true' : 'false' }},
    akademieManagementOpen: {{ request()->routeIs('admin.trainings.*', 'admin.trainers.*') ? 'true' : 'false' }},
    mitarbeiterOrgaOpen: {{ request()->routeIs('admin.dashboard.team*', 'admin.employees.*', 'admin.dashboard.goal-categorization*', 'admin.training-bookings*', 'admin.coaching-overview*') ? 'true' : 'false' }},
    budgetControllingOpen: {{ request()->routeIs('admin.dashboard.budgets*', 'admin.dashboard.plan-budgets*') ? 'true' : 'false' }}
}">
    <div class="p-6 border-b border-slate-700">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <div>
                <h2 class="font-bold text-lg">TD Academy</h2>
                <p class="text-xs text-slate-400">Lernplattform</p>
            </div>
        </div>
    </div>

    <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
        @php
            $user = auth()->user();
            // People Manager: Hat Untergebene (subordinates) ODER hat die entsprechende Rolle
            $isPeopleManager = ($user?->subordinates()->exists() ?? false) || $user?->hasPeopleManagerAccess();
            $isCLevelOrAdmin = $user?->hasAdminAccess();
        @endphp

        {{-- MEINE ENTWICKLUNG (vormals "Lernen") --}}
        <div class="mb-2">
            <button @click="meineEntwicklungOpen = !meineEntwicklungOpen"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700/50 hover:text-white transition-all duration-200">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <span class="font-medium text-sm">Meine Entwicklung</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': meineEntwicklungOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="meineEntwicklungOpen" x-collapse class="mt-1 ml-4 space-y-1">
                <a href="{{ Route::has('user.budget-status') ? route('user.budget-status') : '#' }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('user.budget-status*') 
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Mein Budget-Status</span>
                </a>
            </div>
        </div>

        {{-- AKADEMIE-MANAGEMENT (Gruppierung von Schulungsmanagement & Trainerübersicht) --}}
        @if($isCLevelOrAdmin)
        <div class="mb-2">
            <button @click="akademieManagementOpen = !akademieManagementOpen"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700/50 hover:text-white transition-all duration-200">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="font-medium text-sm">Akademie-Management</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': akademieManagementOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="akademieManagementOpen" x-collapse class="mt-1 ml-4 space-y-1">
                <a href="{{ Route::has('admin.trainings.index') ? route('admin.trainings.index') : '#' }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.trainings.*') 
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Schulungsmanagement</span>
                </a>
                <a href="{{ Route::has('admin.trainers.index') ? route('admin.trainers.index') : '#' }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.trainers.*') 
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span>Trainerübersicht</span>
                </a>
            </div>
        </div>
        @endif

        {{-- MITARBEITER-ORGA (mit Team-Ampel für People Manager) --}}
        @if($isPeopleManager)
        <div class="mb-2">
            <button @click="mitarbeiterOrgaOpen = !mitarbeiterOrgaOpen"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700/50 hover:text-white transition-all duration-200">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <span class="font-medium text-sm">Mitarbeiter-Orga</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': mitarbeiterOrgaOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="mitarbeiterOrgaOpen" x-collapse class="mt-1 ml-4 space-y-1">
                <a href="{{ route('admin.dashboard.team') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.dashboard.team') 
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span>Team-Ampel (Manager)</span>
                </a>
                <a href="{{ route('admin.dashboard.goal-categorization') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.dashboard.goal-categorization') && !request()->has('scope')
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>Ziele kategorisieren</span>
                </a>
                <a href="{{ route('admin.training-bookings') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.training-bookings')
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    <span>Weiterbildung buchen</span>
                </a>
                <a href="{{ route('admin.coaching-overview') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.coaching-overview')
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span>Coach-Übersicht</span>
                </a>
                @if($isCLevelOrAdmin)
                <a href="{{ route('admin.dashboard.goal-categorization', ['scope' => 'all']) }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.dashboard.goal-categorization') && request()->get('scope') === 'all'
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <span>Admin: Ziele kategorisieren</span>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- BUDGET-CONTROLLING (nur C-Level / Admin) --}}
        @if($isCLevelOrAdmin)
        <div class="mb-2">
            <button @click="budgetControllingOpen = !budgetControllingOpen"
                    class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700/50 hover:text-white transition-all duration-200">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <span class="font-medium text-sm">Budget-Controlling</span>
                </div>
                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': budgetControllingOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div x-show="budgetControllingOpen" x-collapse class="mt-1 ml-4 space-y-1">
                <a href="{{ route('admin.dashboard.budgets') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.dashboard.budgets') && !request()->routeIs('admin.dashboard.plan-budgets') 
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    <span>Gesamt-Übersicht (C-Level)</span>
                </a>
                <a href="{{ route('admin.dashboard.plan-budgets') }}"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all duration-200 text-sm
                          {{ request()->routeIs('admin.dashboard.plan-budgets') 
                             ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' 
                             : 'text-slate-400 hover:bg-slate-700/50 hover:text-white' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span>Planbudgets bearbeiten</span>
                </a>
            </div>
        </div>
        @endif

        {{-- ZURÜCK ZUR STARTSEITE --}}
        <div class="pt-4 border-t border-slate-700">
            <a href="/"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700/50 hover:text-white transition-all duration-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="font-medium">Zurück zur Startseite</span>
            </a>
        </div>
    </nav>

    <div class="p-4 border-t border-slate-700">
        <div class="flex items-center gap-3 px-3 py-2">
            <div class="w-8 h-8 bg-slate-600 rounded-full flex items-center justify-center">
                <span class="text-sm font-medium">
                    {{ substr(auth()->user()->name ?? 'G', 0, 1) }}
                </span>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name ?? 'Gast' }}</p>
                <p class="text-xs text-slate-400 truncate">
                    {{ ucfirst(str_replace('_', ' ', auth()->user()->role ?? 'Gast')) }}
                </p>
            </div>
        </div>
    </div>
</aside>
