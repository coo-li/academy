{{-- TD Academy Dark Theme Sidebar --}}
<aside class="dark-sidebar">
    
    {{-- Brand: Nur Bildmarke --}}
    <div class="dark-sidebar-brand">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div class="dark-sidebar-brand-mark">
                <i class="ti ti-school"></i>
            </div>
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
            
            <a href="{{ route('my-budget-status') }}" 
               class="dark-sidebar-item {{ request()->routeIs('my-budget-status') ? 'active' : '' }}">
                <i class="ti ti-layout-dashboard"></i>
                Dashboard
            </a>

            <a href="{{ route('dashboard') }}" 
               class="dark-sidebar-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="ti ti-school"></i>
                Meine td-Schulungen
            </a>

            <a href="{{ route('user.my-milestones') }}" 
               class="dark-sidebar-item {{ request()->routeIs('user.my-milestones') ? 'active' : '' }}">
                <i class="ti ti-target-arrow"></i>
                Meine Milestones
            </a>

            <a href="{{ route('academy.timeline') }}" 
               class="dark-sidebar-item {{ request()->routeIs('academy.timeline') ? 'active' : '' }}">
                <i class="ti ti-timeline-event"></i>
                Meine Timeline
            </a>

            <a href="{{ route('portfolio.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('portfolio.*') ? 'active' : '' }}">
                <i class="ti ti-folder"></i>
                Meine Unterlagen
            </a>

            <a href="{{ route('academy.skill-overview') }}" 
               class="dark-sidebar-item {{ request()->routeIs('academy.skill-overview') ? 'active' : '' }}">
                <i class="ti ti-books"></i>
                td-Schulungskatalog
                <span class="count">48</span>
            </a>
        </div>

        {{-- Mein Team (People Manager / Head of / Admin) --}}
        @if(Auth::user()?->isManager())
        <div class="dark-sidebar-group">
            <div class="dark-sidebar-label">MEIN TEAM</div>

            @if(Auth::user()->isPeopleManagerOrHeadOf())
            <a href="{{ route('manage.employees.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('manage.employees.*') && request()->query('scope') !== 'all' ? 'active' : '' }}">
                <i class="ti ti-users"></i>
                Teamübersicht
            </a>
            @endif

            @if(Auth::user()->isPeopleManagerOrHeadOf())
            <a href="{{ route('admin.dashboard.goal-categorization') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.dashboard.goal-categorization') ? 'active' : '' }}">
                <i class="ti ti-tags"></i>
                Teamziele
            </a>
            @endif

            @if(Auth::user()->isPeopleManagerOrHeadOf())
            <a href="{{ route('admin.dashboard.team') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.dashboard.team') ? 'active' : '' }}">
                <i class="ti ti-wallet"></i>
                Budgetnutzung
            </a>
            @endif

            <a href="{{ route('admin.milestones.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.milestones.*') ? 'active' : '' }}">
                <i class="ti ti-map-pin"></i>
                Milestones verwalten
            </a>

            @if(Auth::user()->isPeopleManagerOrHeadOf())
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

            @if(Auth::user()?->isSchulungsmanager())
            <a href="{{ route('admin.modules.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.modules.*', 'admin.paths.*') ? 'active' : '' }}">
                <i class="ti ti-archive"></i>
                Struktur-Orga
            </a>

            <a href="{{ route('admin.methods.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.methods.*') ? 'active' : '' }}">
                <i class="ti ti-bulb"></i>
                Methoden-Orga
            </a>
            @endif

            @if(Auth::user()?->isTrainer())
            <a href="{{ route('trainer.schulungen.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('trainer.schulungen.*') ? 'active' : '' }}">
                <i class="ti ti-book-2"></i>
                Meine Schulungsinhalte
            </a>

            <a href="{{ route('trainer.termine.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('trainer.termine.*') ? 'active' : '' }}">
                <i class="ti ti-calendar-event"></i>
                Mein Terminmanagement
            </a>

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

            <a href="{{ route('admin.dashboard.budgets') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.dashboard.budgets') ? 'active' : '' }}">
                <i class="ti ti-chart-pie"></i>
                Budget-Overview
            </a>

            <a href="{{ route('admin.budget.rates') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.budget.rates') ? 'active' : '' }}">
                <i class="ti ti-calculator"></i>
                Stundensätze pflegen
            </a>

            <a href="{{ route('admin.budget.rules') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.budget.rules') ? 'active' : '' }}">
                <i class="ti ti-list-check"></i>
                Budget-Regeln
            </a>

            <a href="{{ route('admin.budget.assignments') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.budget.assignments') ? 'active' : '' }}">
                <i class="ti ti-users-plus"></i>
                MA-Budgets zuweisen
            </a>

            <a href="{{ route('admin.matrix.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.matrix.*') ? 'active' : '' }}">
                <i class="ti ti-layout-grid"></i>
                Karriere-Matrix
            </a>
        </div>
        @endif

        {{-- System --}}
        @if(Auth::user()?->isAdmin())
        <div class="dark-sidebar-group">
            <div class="dark-sidebar-label">SYSTEM</div>

            <a href="{{ route('admin.users.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="ti ti-user-cog"></i>
                Nutzerverwaltung
            </a>

            <a href="{{ route('admin.settings.index') }}" 
               class="dark-sidebar-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                <i class="ti ti-settings"></i>
                Einstellungen
            </a>
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
                    Mitarbeitender
                @endforelse
            </div>
        </div>
    </div>
</aside>
