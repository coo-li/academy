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
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <!-- Global Search -->
        <div class="hidden md:block relative" x-data="globalSearch()" @click.away="showResults = false" @keydown.escape.window="showResults = false">
            <div class="input-group">
                <svg class="input-group-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="search"
                       class="input-field w-64"
                       placeholder="Suchen..."
                       x-model="query"
                       @input.debounce.300ms="search()"
                       @focus="if (Object.keys(results).length) showResults = true"
                       @keydown.arrow-down.prevent="moveDown()"
                       @keydown.arrow-up.prevent="moveUp()"
                       @keydown.enter.prevent="goToSelected()">
            </div>

            <div x-show="showResults" x-cloak
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="dropdown-menu right-0 left-0 w-full mt-1" style="min-width: 320px;">

                <template x-if="loading">
                    <div class="px-4 py-3 text-sm text-surface-500 text-center">Suche läuft...</div>
                </template>

                <template x-if="!loading && (!results.modules || !results.modules.length) && query.length >= 2">
                    <div class="px-4 py-3 text-sm text-surface-500 text-center">Keine Module gefunden für „<span x-text="query" class="font-medium"></span>"</div>
                </template>

                <template x-for="(item, idx) in (results.modules || [])" :key="'m-'+idx">
                    <a :href="item.url"
                       class="dropdown-item flex flex-col gap-0.5"
                       :class="{ 'bg-surface-100': idx === selectedIndex }"
                       @mouseenter="selectedIndex = idx">
                        <span class="font-medium text-sm" x-text="item.title"></span>
                        <span class="text-xs text-surface-500" x-text="item.subtitle"></span>
                    </a>
                </template>
            </div>
        </div>
        
        <!-- Notifications -->
        <div class="notifications-dropdown" x-data="notificationBell()" x-init="load()">
            <button @click="toggle()" class="notifications-trigger relative">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                </svg>
                <span x-show="unreadCount > 0" x-cloak
                      class="absolute -top-1 -right-1 w-4 h-4 bg-ui-error text-white text-xs rounded-full flex items-center justify-center"
                      x-text="unreadCount > 9 ? '9+' : unreadCount"></span>
            </button>
            
            <div x-show="open" x-cloak @click.away="open = false"
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="dropdown-menu right-0 w-80">
                <div class="dropdown-header flex items-center justify-between">
                    <span>Benachrichtigungen</span>
                    <button x-show="unreadCount > 0" @click.stop="markAllRead()" class="text-xs text-brand-primary hover:underline">
                        Alle gelesen
                    </button>
                </div>

                <template x-if="!items.length">
                    <div class="px-4 py-6 text-sm text-surface-500 text-center">Keine Benachrichtigungen</div>
                </template>

                <template x-for="item in items" :key="item.id">
                    <a :href="item.url" @click="markRead(item)" class="dropdown-item flex items-start gap-3 py-2.5">
                        <span class="mt-1.5 flex-shrink-0 w-2 h-2 rounded-full"
                              :class="item.read ? 'bg-surface-300' : 'bg-brand-primary'"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm leading-snug" :class="item.read ? 'text-surface-500' : 'text-surface-900 font-medium'" x-text="item.message"></p>
                            <p class="text-xs text-surface-400 mt-0.5" x-text="item.created_at"></p>
                        </div>
                        <span class="flex-shrink-0 mt-0.5">
                            <template x-if="item.type === 'enrollment'">
                                <svg class="w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </template>
                            <template x-if="item.type === 'assignment'">
                                <svg class="w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </template>
                            <template x-if="item.type === 'interest'">
                                <svg class="w-4 h-4 text-surface-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            </template>
                        </span>
                    </a>
                </template>
            </div>
        </div>
        
        <!-- User Menu -->
        <div class="user-menu" x-data="{ open: false }">
            <button @click="open = !open" class="user-menu-trigger">
                <div class="avatar-sm">
                    <span>{{ Auth::user()->initials }}</span>
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
                @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.settings.index') }}" class="dropdown-item">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Einstellungen
                </a>
                @endif
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

