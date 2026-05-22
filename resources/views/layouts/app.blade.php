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
            
            {{-- Admin Warning --}}
            @if(Auth::user()?->isAdmin())
                @php
                    $teamsWithoutPeopleManager = \App\Models\Team::whereDoesntHave('managers', function ($q) {
                        $q->whereHas('roles', fn ($r) => $r->where('slug', 'people_manager'));
                    })->pluck('name');
                @endphp
                @if($teamsWithoutPeopleManager->isNotEmpty())
                    <div class="px-8 pt-4">
                        <div class="flex items-start gap-3 p-4 rounded-dark bg-dark-st-quiz/10 border border-dark-st-quiz/30 text-sm">
                            <i class="ti ti-alert-triangle text-dark-st-quiz text-lg flex-shrink-0 mt-0.5"></i>
                            <div class="text-dark-tx-2">
                                <strong class="text-dark-st-quiz">{{ $teamsWithoutPeopleManager->count() }} Team(s) ohne People Manager:</strong>
                                {{ $teamsWithoutPeopleManager->join(', ') }}.
                                <a href="{{ route('admin.users.index') }}" class="text-dark-tuerkis underline font-semibold ml-1">Zur Nutzerverwaltung →</a>
                            </div>
                        </div>
                    </div>
                @endif
            @endif

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
