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
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <svg class="w-8 h-8 text-brand-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
            </svg>
            <span class="text-lg font-bold text-white">td Academy</span>
        </a>
    </div>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">

        {{-- === Alle Rollen: Lern-Bereich === --}}
        <div class="sidebar-section">
            <div class="sidebar-section-title">Lernen</div>
            
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Meine Academy
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
                Digitale Mappe
            </a>
        </div>

        {{-- === Teacher-Rollen: Verwaltung (admin, people_manager, head_of, trainer) === --}}
        @if(Auth::user()?->isTeacher())
        <div class="sidebar-section">
            <div class="sidebar-section-title">Verwaltung</div>

            @if(Auth::user()->isManager())
            <a href="{{ route('admin.modules.index') }}" class="{{ request()->routeIs('admin.modules.*', 'admin.paths.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Struktur-Verwaltung
            </a>
            @endif

            <a href="{{ route('admin.skill-categories.index') }}" class="{{ request()->routeIs('admin.skill-categories.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                </svg>
                Skill-Kategorien
            </a>

            <a href="{{ route('admin.methods.index') }}" class="{{ request()->routeIs('admin.methods.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                Methoden
            </a>

            @if(Auth::user()->isManager())
            <a href="{{ route('manage.employees.index') }}" class="{{ request()->routeIs('manage.employees.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Meine Mitarbeiter
            </a>
            @endif

            <a href="{{ route('teacher.dashboard') }}" class="{{ request()->routeIs('teacher.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Lehrer-Konsole
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

            <a href="{{ route('admin.matrix.index') }}" class="{{ request()->routeIs('admin.matrix.*') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                </svg>
                Karriere-Matrix
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
    <div class="mt-auto p-4 border-t border-surface-200">
        <div class="flex items-center gap-3">
            <div class="avatar-sm">
                <span>{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-brand-dark truncate">{{ Auth::user()->name ?? 'User' }}</div>
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
