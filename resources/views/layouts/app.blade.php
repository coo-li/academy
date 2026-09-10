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
    
    {{-- Scripts & Styles --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="dark-app" x-data="{ sidebarOpen: true, mobileMenuOpen: false }">
    {{-- Impersonation Banner --}}
    @if(session('impersonating_from'))
        <div class="fixed top-0 left-0 right-0 z-[100] bg-amber-500 text-amber-900 px-4 py-2 text-center shadow-lg">
            <div class="flex items-center justify-center gap-4">
                <span class="flex items-center gap-2">
                    <i class="ti ti-user-check text-lg"></i>
                    <span class="font-semibold">Du siehst die Anwendung als <strong>{{ Auth::user()->name }}</strong></span>
                </span>
                <form action="{{ route('impersonate.stop') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1 bg-amber-700 text-white rounded-lg hover:bg-amber-800 transition-colors text-sm font-medium">
                        <i class="ti ti-arrow-back-up"></i>
                        Zurück zu {{ session('impersonating_from_name') }}
                    </button>
                </form>
            </div>
        </div>
        <div class="h-10"></div> {{-- Spacer for fixed banner --}}
    @endif

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
            
            {{-- Dark Topbar --}}
            @include('layouts.dark-topbar')
            
            {{-- Admin Warning removed - managers() relation not available --}}

            {{-- Page Content --}}
            <main class="flex-1 overflow-auto">
                <div class="p-8 w-full max-w-[1600px]">
                    {{ $slot }}
                </div>
            </main>
            
            {{-- Footer --}}
            @include('layouts.dark-footer')
        </div>
    </div>
    
    {{-- Toasts --}}
    @include('layouts.toasts')

    @livewireScripts
    @stack('scripts')
</body>
</html>
