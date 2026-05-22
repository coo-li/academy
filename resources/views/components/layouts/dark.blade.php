<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'td Academy') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="180x180" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    
    {{-- Tabler Icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tabler-icons/2.47.0/tabler-icons.min.css">
    
    {{-- Scripts & Styles (includes Mulish + Lato fonts) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="dark-app" x-data="{ mobileMenuOpen: false }">
    <div class="flex min-h-screen">
        
        {{-- Mobile Sidebar Backdrop --}}
        <div x-show="mobileMenuOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 z-30 bg-black/70 lg:hidden"></div>
        
        {{-- Dark Sidebar --}}
        @include('layouts.dark-sidebar')
        
        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-w-0 lg:ml-[264px]">
            
            {{-- Topbar --}}
            <div class="dark-topbar">
                {{-- Mobile Menu Button --}}
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="lg:hidden dark-icon-btn">
                    <i class="ti ti-menu-2"></i>
                </button>
                
                {{-- Breadcrumb --}}
                <div class="dark-topbar-crumb">
                    @hasSection('breadcrumb')
                        @yield('breadcrumb')
                    @else
                        Dashboard / <b>{{ $title ?? 'Übersicht' }}</b>
                    @endif
                </div>
                
                <div class="dark-topbar-spacer"></div>
                
                {{-- Notifications --}}
                <div class="dark-icon-btn">
                    <i class="ti ti-bell"></i>
                    <span class="dot"></span>
                </div>
                
                {{-- Help --}}
                <div class="dark-icon-btn">
                    <i class="ti ti-help-circle"></i>
                </div>
            </div>

            {{-- Page Content --}}
            <main class="flex-1 overflow-auto">
                <div class="dark-content">
                    {{ $slot }}
                </div>
            </main>
            
        </div>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
