<!-- Sidebar -->
<aside class="sidebar fixed inset-y-0 left-0 z-40 transform transition-transform duration-200 ease-in-out"
       :class="{ 
           '-translate-x-full': !mobileMenuOpen, 
           'translate-x-0': mobileMenuOpen,
           'lg:-translate-x-full': !sidebarOpen,
           'lg:translate-x-0': sidebarOpen 
       }">
    
    <!-- Brand -->
    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="flex items-center">
            <img src="{{ asset('images/logo-white.png') }}" alt="trafficdesign Academy" class="h-12 w-auto">
        </a>
    </div>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">

        {{-- === Alle Rollen: Meine Entwicklung (vormals "Lernen") === --}}
        <div class="sidebar-section">
            <div class="sidebar-section-title">Meine Entwicklung</div>
            
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Meine Academy
            </a>

            <a href="{{ route('academy.skill-overview') }}" class="{{ request()->routeIs('academy.skill-overview') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Schulungskatalog
            </a>

            <a href="{{ route('academy.timeline') }}" class="{{ request()->routeIs('academy.timeline') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Meine Timeline
            </a>

            <a href="{{ route('portfolio.index') }}" class="{{ request()->routeIs('portfolio.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path>
                </svg>
                Schulungsunterlagen
            </a>

            <a href="{{ route('my-budget-status') }}" class="{{ request()->routeIs('my-budget-status') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Mein Budget-Status
            </a>
        </div>

        {{-- === Mitarbeiterorga (People Manager / Head of / Admin) === --}}
        @if(Auth::user()?->isManager())
        <div class="sidebar-section">
            <div class="sidebar-section-title">Mein Team</div>

            @if(Auth::user()->isPeopleManagerOrHeadOf())
            <a href="{{ route('manage.employees.index') }}" class="{{ request()->routeIs('manage.employees.*') && request()->query('scope') !== 'all' ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Module & Milestones
            </a>
            @endif

            @if(Auth::user()->isAdmin())
            <a href="{{ route('manage.employees.index', ['scope' => 'all']) }}" class="{{ request()->routeIs('manage.employees.*') && (request()->query('scope') === 'all' || !Auth::user()->isPeopleManagerOrHeadOf()) ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197V21"></path>
                </svg>
                Admin: Module & Milestones
            </a>
            @endif

            <a href="{{ route('admin.milestones.index') }}" class="{{ request()->routeIs('admin.milestones.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                Milestones verwalten
            </a>

            <a href="{{ route('admin.dashboard.team') }}" class="{{ request()->routeIs('admin.dashboard.team') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                Budget-Ampel
            </a>

            <a href="{{ route('admin.training-bookings') }}" class="{{ request()->routeIs('admin.training-bookings') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Weiterbildung buchen
            </a>
        </div>
        @endif

        {{-- === Akademie-Management (Schulungsmanager / Trainer / Admin) === --}}
        @if(Auth::user()?->isSchulungsmanager() || Auth::user()?->isTrainer())
        <div class="sidebar-section">
            <div class="sidebar-section-title">Akademie-Management</div>

            {{-- Schulungsmanagement-Bereich --}}
            @if(Auth::user()?->isSchulungsmanager())
            <a href="{{ route('admin.modules.index') }}" class="{{ request()->routeIs('admin.modules.*', 'admin.paths.*', 'admin.skill-categories.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Schulungsmanagement
            </a>

            <a href="{{ route('admin.methods.index') }}" class="{{ request()->routeIs('admin.methods.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                Methoden
            </a>
            @endif

            {{-- Trainer-Bereich --}}
            @if(Auth::user()?->isTrainer())
            <a href="{{ route('trainer.termine.index') }}" class="{{ request()->routeIs('trainer.termine.*') && request()->query('scope') !== 'all' ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Terminmanagement
            </a>

            @if(Auth::user()->isAdmin())
            <a href="{{ route('trainer.termine.index', ['scope' => 'all']) }}" class="{{ request()->routeIs('trainer.termine.*') && request()->query('scope') === 'all' ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Alle Termine
            </a>
            @endif

            <a href="{{ route('trainer.schulungen.index') }}" class="{{ request()->routeIs('trainer.schulungen.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                </svg>
                Schulungsinhalte organisieren
            </a>

            <a href="{{ route('trainer.teilnehmer.index') }}" class="{{ request()->routeIs('trainer.teilnehmer.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                Teilnehmermanagement
            </a>
            @endif
        </div>
        @endif

        {{-- === Budget-Controlling (Admin / C-Level) === --}}
        @if(Auth::user()?->isAdmin())
        <div class="sidebar-section">
            <div class="sidebar-section-title">Unternehmen</div>

            <a href="{{ route('admin.dashboard.budgets') }}" class="{{ request()->routeIs('admin.dashboard.budgets') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                Budget-Gesamtübersicht
            </a>

            <a href="{{ route('admin.budget.rates') }}" class="{{ request()->routeIs('admin.budget.rates') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Stundensätze pflegen
            </a>

            <a href="{{ route('admin.matrix.index') }}" class="{{ request()->routeIs('admin.matrix.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                </svg>
                Karriere-Matrix
            </a>
        </div>
        @endif

        {{-- === Nur Admin: System === --}}
        @if(Auth::user()?->isAdmin())
        <div class="sidebar-section">
            <div class="sidebar-section-title">System</div>

            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m3 5.197V21"></path>
                </svg>
                Nutzerverwaltung
            </a>

            <a href="{{ route('admin.settings.index') }}" class="{{ request()->routeIs('admin.settings.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Einstellungen
            </a>
        </div>
        @endif

        {{-- === Alle: Profil === --}}
        <div class="sidebar-section">
            <div class="sidebar-section-title">Konto</div>

            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.edit') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Profil
            </a>
        </div>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="mt-auto p-4 border-t border-surface-100">
        <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-surface-50 transition-colors duration-200">
            <div class="avatar-sm ring-2 ring-brand-primary/20">
                <span>{{ Auth::user()->initials }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-semibold text-brand-dark truncate">{{ Auth::user()->name ?? 'User' }}</div>
                <div class="flex flex-wrap gap-1 mt-0.5">
                    @forelse(Auth::user()->roles ?? [] as $role)
                        <span class="text-xs text-surface-500">{{ $role->name }}</span>
                        @if(!$loop->last)<span class="text-xs text-surface-300">&middot;</span>@endif
                    @empty
                        <span class="text-xs text-surface-500">Mitarbeitender</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</aside>
