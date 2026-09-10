{{-- Dark Theme Topbar --}}
<header class="dark-topbar sticky top-0 z-20 bg-dark-bg">
    {{-- Left: Mobile Menu + Breadcrumb --}}
    <div class="flex items-center gap-4">
        {{-- Mobile Menu Button --}}
        <button @click="mobileMenuOpen = !mobileMenuOpen" class="dark-icon-btn lg:hidden">
            <i class="ti ti-menu-2"></i>
        </button>
        
        {{-- Breadcrumb --}}
        <nav class="dark-topbar-crumb hidden sm:block">
            <a href="{{ route('dashboard') }}" class="hover:text-dark-tx transition-colors">Dashboard</a>
            <span class="mx-2">/</span>
            <b>@yield('page-title', 'Übersicht')</b>
        </nav>
    </div>
    
    <div class="dark-topbar-spacer"></div>
    
    {{-- Right: Search, Notifications, User --}}
    <div class="flex items-center gap-3">
        
        {{-- Global Search --}}
        <div class="hidden md:block relative" x-data="globalSearch()" @click.away="showResults = false" @keydown.escape.window="showResults = false">
            <div class="dark-sidebar-search" style="margin: 0; width: 220px;">
                <i class="ti ti-search"></i>
                <input type="search"
                       placeholder="Suchen..."
                       x-model="query"
                       @input.debounce.300ms="search()"
                       @focus="if (Object.keys(results).length) showResults = true"
                       @keydown.arrow-down.prevent="moveDown()"
                       @keydown.arrow-up.prevent="moveUp()"
                       @keydown.enter.prevent="goToSelected()">
            </div>

            {{-- Search Results Dropdown --}}
            <div x-show="showResults" x-cloak
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute top-full right-0 mt-2 w-80 bg-dark-card border border-dark-line rounded-dark shadow-lg py-2 z-50">

                <template x-if="loading">
                    <div class="px-4 py-3 text-sm text-dark-tx-3 text-center">Suche läuft...</div>
                </template>

                <template x-if="!loading && (!results.modules || !results.modules.length) && query.length >= 2">
                    <div class="px-4 py-3 text-sm text-dark-tx-3 text-center">Keine Ergebnisse für „<span x-text="query" class="text-dark-tx"></span>"</div>
                </template>

                <template x-for="(item, idx) in (results.modules || [])" :key="'m-'+idx">
                    <a :href="item.url"
                       class="flex flex-col gap-0.5 px-4 py-2 hover:bg-dark-card-hover transition-colors"
                       :class="{ 'bg-dark-tuerkis-dark': idx === selectedIndex }"
                       @mouseenter="selectedIndex = idx">
                        <span class="font-bold text-sm text-dark-tx" x-text="item.title"></span>
                        <span class="text-xs text-dark-tx-3" x-text="item.subtitle"></span>
                    </a>
                </template>
            </div>
        </div>
        
        {{-- Notifications --}}
        <div class="relative" x-data="notificationBell()" x-init="load()">
            <button @click="toggle()" class="dark-icon-btn">
                <i class="ti ti-bell"></i>
                <span x-show="unreadCount > 0" x-cloak class="dot"></span>
            </button>
            
            {{-- Notifications Dropdown --}}
            <div x-show="open" x-cloak @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute top-full right-0 mt-2 w-80 bg-dark-card border border-dark-line rounded-dark shadow-lg z-50">
                
                <div class="px-4 py-3 border-b border-dark-line flex items-center justify-between">
                    <span class="font-bold text-sm text-dark-tx">Benachrichtigungen</span>
                    <button x-show="unreadCount > 0" @click.stop="markAllRead()" class="text-xs text-dark-tuerkis hover:text-dark-tuerkis-hover font-bold">
                        Alle gelesen
                    </button>
                </div>

                <template x-if="!items.length">
                    <div class="px-4 py-6 text-sm text-dark-tx-3 text-center">Keine Benachrichtigungen</div>
                </template>

                <div class="max-h-80 overflow-y-auto">
                    <template x-for="item in items" :key="item.id">
                        <a :href="item.url" @click="markRead(item)" 
                           class="flex items-start gap-3 px-4 py-3 hover:bg-dark-card-hover transition-colors border-b border-dark-line last:border-0">
                            <span class="mt-1.5 flex-shrink-0 w-2 h-2 rounded-full"
                                  :class="item.read ? 'bg-dark-tx-3' : 'bg-dark-tuerkis'"></span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm leading-snug" :class="item.read ? 'text-dark-tx-3' : 'text-dark-tx font-medium'" x-text="item.message"></p>
                                <p class="text-xs text-dark-tx-3 mt-0.5" x-text="item.created_at"></p>
                            </div>
                        </a>
                    </template>
                </div>
            </div>
        </div>
        
        {{-- User Menu --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-dark-card-hover transition-colors">
                <div class="dark-sidebar-avatar w-8 h-8 text-xs">
                    {{ Auth::user()->initials ?? 'TD' }}
                </div>
                <span class="hidden sm:inline text-sm font-bold text-dark-tx">{{ Auth::user()->name ?? 'User' }}</span>
                <i class="ti ti-chevron-down text-dark-tx-3 text-sm"></i>
            </button>
            
            {{-- User Dropdown --}}
            <div x-show="open" x-cloak @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="absolute top-full right-0 mt-2 w-56 bg-dark-card border border-dark-line rounded-dark shadow-lg z-50">
                
                <div class="px-4 py-3 border-b border-dark-line">
                    <div class="text-xs text-dark-tx-3">Angemeldet als</div>
                    <div class="text-sm font-bold text-dark-tx truncate">{{ Auth::user()->email ?? '' }}</div>
                </div>
                
                <div class="py-2">
                    <a href="{{ Route::has('profile.edit') ? route('profile.edit') : '#' }}" class="flex items-center gap-3 px-4 py-2 text-sm text-dark-tx-2 hover:bg-dark-card-hover hover:text-dark-tx transition-colors">
                        <i class="ti ti-user text-lg"></i>
                        Profil bearbeiten
                    </a>
                    @if(auth()->user()->isAdmin() && Route::has('admin.settings.index'))
                    <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-dark-tx-2 hover:bg-dark-card-hover hover:text-dark-tx transition-colors">
                        <i class="ti ti-settings text-lg"></i>
                        Einstellungen
                    </a>
                    @endif
                </div>
                
                <div class="border-t border-dark-line py-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-3 px-4 py-2 text-sm text-dark-st-quiz hover:bg-dark-st-quiz/10 transition-colors w-full">
                            <i class="ti ti-logout text-lg"></i>
                            Abmelden
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
