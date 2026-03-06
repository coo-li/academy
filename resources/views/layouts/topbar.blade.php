<!-- Topbar -->
<header class="topbar sticky top-0 z-20">
    <div class="topbar-left">
        <!-- Mobile Menu Button -->
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="btn-icon lg:hidden">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <!-- Desktop Sidebar Toggle -->
        <button @click="$store.sidebar.toggle(); sidebarOpen = $store.sidebar.open" class="btn-icon hidden lg:flex">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        
        <!-- Breadcrumbs -->
        <nav class="breadcrumbs hidden sm:flex">
            <a href="{{ route('dashboard') }}" class="breadcrumbs-item">Dashboard</a>
            <span class="breadcrumbs-separator">/</span>
            <span class="breadcrumbs-current">@yield('page-title', 'Übersicht')</span>
        </nav>
    </div>
    
    <div class="topbar-right">
        <!-- Search -->
        <div class="hidden md:block">
            <div class="input-group">
                <svg class="input-group-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="search" class="input-field w-64" placeholder="Suchen...">
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="notifications-dropdown" x-data="{ open: false }">
            <button @click="open = !open" class="notifications-trigger relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <span class="absolute -top-1 -right-1 w-4 h-4 bg-ui-error text-white text-xs rounded-full flex items-center justify-center">3</span>
            </button>
            
            <div x-show="open" x-cloak @click.away="open = false" 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="dropdown-menu right-0 w-80">
                <div class="dropdown-header">Benachrichtigungen</div>
                <a href="#" class="dropdown-item">
                    <span class="w-2 h-2 bg-ui-info rounded-full"></span>
                    Neue Nachricht erhalten
                </a>
                <a href="#" class="dropdown-item">
                    <span class="w-2 h-2 bg-ui-success rounded-full"></span>
                    Aufgabe abgeschlossen
                </a>
                <a href="#" class="dropdown-item">
                    <span class="w-2 h-2 bg-ui-warning rounded-full"></span>
                    Warnung: Speicher fast voll
                </a>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item text-brand-primary">Alle anzeigen</a>
            </div>
        </div>
        
        <!-- User Menu -->
        <div class="user-menu" x-data="{ open: false }">
            <button @click="open = !open" class="user-menu-trigger">
                <div class="avatar-sm">
                    <span>{{ substr(Auth::user()->name ?? 'U', 0, 2) }}</span>
                </div>
                <span class="hidden sm:inline text-sm">{{ Auth::user()->name ?? 'User' }}</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <div x-show="open" x-cloak @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="dropdown-menu right-0">
                <div class="dropdown-header">{{ Auth::user()->email ?? '' }}</div>
                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    Profil bearbeiten
                </a>
                <a href="#" class="dropdown-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Einstellungen
                </a>
                <div class="dropdown-divider"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-item-danger w-full text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Abmelden
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

