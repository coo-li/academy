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
            <svg class="w-8 h-8 text-brand-primary" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path>
            </svg>
            <span class="text-lg font-bold text-white">{{ config('app.name', 'Laravel') }}</span>
        </a>
    </div>
    
    <!-- Navigation -->
    <nav class="sidebar-nav">
        <!-- Main Section -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Hauptmenü</div>
            
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dashboard
            </a>
            
            @if(Route::has('demo'))
            <a href="{{ route('demo') }}" class="{{ request()->routeIs('demo') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path>
                </svg>
                UI Demo
            </a>
            @endif
        </div>
        
        <!-- Settings Section -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Einstellungen</div>
            
            <a href="{{ route('profile.edit') }}" class="{{ request()->routeIs('profile.edit') ? 'sidebar-link-active' : 'sidebar-link' }}">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Profil
            </a>
            
            <a href="#" class="sidebar-link">
                <svg class="sidebar-link-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Einstellungen
            </a>
        </div>
    </nav>
    
    <!-- Sidebar Footer -->
    <div class="mt-auto p-4 border-t border-surface-200">
        <div class="flex items-center gap-3">
            <div class="avatar-sm">
                <span>{{ substr(Auth::user()->name ?? 'U', 0, 2) }}</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-brand-dark truncate">{{ Auth::user()->name ?? 'User' }}</div>
                <div class="text-xs text-surface-500 truncate">{{ Auth::user()->email ?? '' }}</div>
            </div>
        </div>
    </div>
</aside>

