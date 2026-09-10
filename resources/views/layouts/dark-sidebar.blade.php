{{-- TD Academy Dark Theme Sidebar --}}
<aside class="dark-sidebar">
    
    {{-- Brand: Logo/Bildmarke --}}
    <div class="dark-sidebar-brand">
        <a href="{{ route('user.budget-status') }}" class="flex items-center justify-center" title="Mein Dashboard">
            <img src="{{ asset('favicon.png') }}" alt="TD Academy" class="w-10 h-10 rounded-lg hover:scale-105 transition-transform duration-200">
        </a>
    </div>

    {{-- Suche --}}
    <div class="dark-sidebar-search">
        <i class="ti ti-search"></i>
        <input type="text" placeholder="Modul, Milestone, Trainer …">
    </div>

    {{-- Navigation --}}
    <nav class="dark-sidebar-nav">

        {{-- Meine Entwicklung --}}
        <div class="dark-sidebar-group">
            <div class="dark-sidebar-label">MEINE ENTWICKLUNG</div>
            
            @if(Route::has('user.budget-status'))
            <a href="{{ route('user.budget-status') }}" 
               class="dark-sidebar-item {{ request()->routeIs('user.budget-status') ? 'active' : '' }}">
                <i class="ti ti-layout-dashboard"></i>
                Dashboard
            </a>
            @endif

            @if(Route::has('dashboard'))
            <a href="{{ route('dashboard') }}" 
               class="dark-sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="ti ti-school"></i>
                Meine td-Schulungen
            </a>
            @endif

            @if(Route::has('user.my-milestones'))
            <a href="{{ route('user.my-milestones') }}" 
               class="dark-sidebar-item {{ request()->routeIs('user.my-milestones') ? 'active' : '' }}">
                <i class="ti ti-target-arrow"></i>
                Meine Milestones
            </a>
            @endif

            @if(Route::has('academy.timeline'))
            <a href="{{ route('academy.timeline') }}" 
               class="dark-sidebar-item {{ request()->routeIs('academy.timeline') ? 'active' : '' }}">
                <i class="ti ti-timeline-event"></i>
                Meine Timeline
            </a>
            @endif

            @if(Route::has('portfolio.index'))
            <a href="{{ route('portfolio.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('portfolio.*') ? 'active' : '' }}">
                <i class="ti ti-folder"></i>
                Meine Unterlagen
            </a>
            @endif

            @if(Route::has('academy.skill-overview'))
            <a href="{{ route('academy.skill-overview') }}" 
               class="dark-sidebar-item {{ request()->routeIs('academy.skill-overview') ? 'active' : '' }}">
                <i class="ti ti-books"></i>
                td-Schulungskatalog
                <span class="count">48</span>
            </a>
            @endif
        </div>

        {{-- Mein Team (People Manager / Head of / Admin) --}}
        @if(Auth::user()?->isManager())
        <div class="dark-sidebar-group">
            <div class="dark-sidebar-label">MEIN TEAM</div>

            @if(Auth::user()->isPeopleManagerOrHeadOf() && Route::has('manage.employees.index'))
            <a href="{{ route('manage.employees.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('manage.employees.*') && request()->query('scope') !== 'all' ? 'active' : '' }}">
                <i class="ti ti-users"></i>
                Teamübersicht
            </a>
            @endif

            @if(Auth::user()->isPeopleManagerOrHeadOf() && Route::has('admin.dashboard.goal-categorization'))
            <a href="{{ route('admin.dashboard.goal-categorization') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.dashboard.goal-categorization') ? 'active' : '' }}">
                <i class="ti ti-tags"></i>
                Teamziele
            </a>
            @endif

            @if(Auth::user()->isPeopleManagerOrHeadOf() && Route::has('admin.dashboard.team'))
            <a href="{{ route('admin.dashboard.team') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.dashboard.team') ? 'active' : '' }}">
                <i class="ti ti-wallet"></i>
                Budgetnutzung
            </a>
            @endif

            @if(Route::has('admin.milestones.index'))
            <a href="{{ route('admin.milestones.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.milestones.*') ? 'active' : '' }}">
                <i class="ti ti-map-pin"></i>
                Milestones verwalten
            </a>
            @endif

            @if(Auth::user()->isPeopleManagerOrHeadOf() && Route::has('admin.training-bookings'))
            <a href="{{ route('admin.training-bookings') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.training-bookings') ? 'active' : '' }}">
                <i class="ti ti-book"></i>
                Weiterbildung buchen
            </a>
            @endif
        </div>
        @endif

        {{-- Schulungsmanagement (Schulungsmanager / Trainer) --}}
        @if(Auth::user()?->isSchulungsmanager() || Auth::user()?->isTrainer())
        <div class="dark-sidebar-group">
            <div class="dark-sidebar-label">SCHULUNGSMANAGEMENT</div>

            @if(Auth::user()?->isSchulungsmanager() && Route::has('admin.modules.index'))
            <a href="{{ route('admin.modules.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.modules.*', 'admin.paths.*') ? 'active' : '' }}">
                <i class="ti ti-archive"></i>
                Struktur-Orga
            </a>
            @endif

            @if(Auth::user()?->isSchulungsmanager() && Route::has('admin.methods.index'))
            <a href="{{ route('admin.methods.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.methods.*') ? 'active' : '' }}">
                <i class="ti ti-bulb"></i>
                Methoden-Orga
            </a>
            @endif

            @if(Auth::user()?->isTrainer() && Route::has('trainer.schulungen.index'))
            <a href="{{ route('trainer.schulungen.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('trainer.schulungen.*') ? 'active' : '' }}">
                <i class="ti ti-book-2"></i>
                Meine Schulungsinhalte
            </a>
            @endif

            @if(Auth::user()?->isTrainer() && Route::has('trainer.termine.index'))
            <a href="{{ route('trainer.termine.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('trainer.termine.*') ? 'active' : '' }}">
                <i class="ti ti-calendar-event"></i>
                Mein Terminmanagement
            </a>
            @endif

            @if(Auth::user()?->isTrainer() && Route::has('trainer.teilnehmer.index'))
            <a href="{{ route('trainer.teilnehmer.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('trainer.teilnehmer.*') ? 'active' : '' }}">
                <i class="ti ti-clipboard-check"></i>
                Mein Teilnehmermanagement
            </a>
            @endif
        </div>
        @endif

        {{-- Admin-Bereich --}}
        @if(Auth::user()?->isAdmin())
        <div class="dark-sidebar-group">
            <div class="dark-sidebar-label">ADMIN-BEREICH</div>

            @if(Route::has('admin.dashboard.budgets'))
            <a href="{{ route('admin.dashboard.budgets') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.dashboard.budgets') ? 'active' : '' }}">
                <i class="ti ti-chart-pie"></i>
                Budget-Overview
            </a>
            @endif

            @if(Route::has('admin.budget.rates'))
            <a href="{{ route('admin.budget.rates') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.budget.rates') ? 'active' : '' }}">
                <i class="ti ti-calculator"></i>
                Stundensätze pflegen
            </a>
            @endif

            @if(Route::has('admin.budget.rules'))
            <a href="{{ route('admin.budget.rules') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.budget.rules') ? 'active' : '' }}">
                <i class="ti ti-list-check"></i>
                Budget-Regeln
            </a>
            @endif

            @if(Route::has('admin.budget.assignments'))
            <a href="{{ route('admin.budget.assignments') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.budget.assignments') ? 'active' : '' }}">
                <i class="ti ti-users-plus"></i>
                MA-Budgets zuweisen
            </a>
            @endif

            @if(Route::has('admin.matrix.index'))
            <a href="{{ route('admin.matrix.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.matrix.*') ? 'active' : '' }}">
                <i class="ti ti-layout-grid"></i>
                Karriere-Matrix
            </a>
            @endif

            @if(Route::has('manage.employees.index'))
            <a href="{{ route('manage.employees.index', ['scope' => 'all']) }}" 
               class="dark-sidebar-item {{ request()->routeIs('manage.employees.*') && request()->query('scope') === 'all' ? 'active' : '' }}">
                <i class="ti ti-users-group"></i>
                Alle Mitarbeitenden
            </a>
            @endif

            @if(Route::has('admin.budget-sync-projects'))
            <a href="{{ route('admin.budget-sync-projects') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.budget-sync-projects') ? 'active' : '' }}">
                <i class="ti ti-refresh"></i>
                Sync-Projekte
            </a>
            @endif
        </div>
        @endif

        {{-- System --}}
        @if(Auth::user()?->isAdmin())
        <div class="dark-sidebar-group">
            <div class="dark-sidebar-label">SYSTEM</div>

            @if(Route::has('admin.users.index'))
            <a href="{{ route('admin.users.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="ti ti-user-cog"></i>
                Nutzerverwaltung
            </a>
            @endif

            @if(Route::has('admin.settings.index'))
            <a href="{{ route('admin.settings.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="ti ti-settings"></i>
                Einstellungen
            </a>
            @endif
        </div>
        @endif

    </nav>

    {{-- User Footer --}}
    <div class="dark-sidebar-user">
        <div class="dark-sidebar-avatar">
            {{ Auth::user()->initials ?? 'TD' }}
        </div>
        <div>
            <div class="dark-sidebar-user-name">{{ Auth::user()->name ?? 'User' }}</div>
            <div class="dark-sidebar-user-role">
                @forelse(Auth::user()->roles ?? [] as $role)
                    {{ $role->name }}@if(!$loop->last) · @endif
                @empty
                    Mitarbeitend
                @endforelse
            </div>
        </div>
    </div>
</aside>
