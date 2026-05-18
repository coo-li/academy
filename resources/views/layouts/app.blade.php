<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="180x180" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface-50 font-sans" x-data="{ sidebarOpen: $store.sidebar.open, mobileMenuOpen: false }">
    <div class="flex min-h-screen">
        
        <!-- Mobile Sidebar Backdrop -->
        <div x-show="mobileMenuOpen" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="mobileMenuOpen = false"
             class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>
        
        <!-- Sidebar -->
        @include('layouts.sidebar')
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0 lg:ml-64" :class="{ 'lg:ml-64': sidebarOpen, 'lg:!ml-0': !sidebarOpen }">
            
            <!-- Topbar -->
            @include('layouts.topbar')
            
            {{-- Admin: Warning for teams without a People Manager --}}
            @php
                // #region agent log
                $__dbgLogPath = '/tmp/debug-5c18c4.log';
                $__dbgUser = Auth::user();
                $__dbgData = ['hypothesisId'=>'H1','sessionId'=>'5c18c4','location'=>'app.blade.php:40','message'=>'Auth check','data'=>['user_exists'=>$__dbgUser !== null,'user_name'=>$__dbgUser?->name,'user_id'=>$__dbgUser?->id],'timestamp'=>round(microtime(true)*1000)];
                file_put_contents($__dbgLogPath, json_encode($__dbgData)."\n", FILE_APPEND);
                // #endregion

                // #region agent log
                $__dbgIsAdmin = $__dbgUser?->isAdmin() ?? false;
                $__dbgRoles = $__dbgUser?->roles?->pluck('slug')->toArray() ?? [];
                $__dbgData2 = ['hypothesisId'=>'H2','sessionId'=>'5c18c4','location'=>'app.blade.php:41','message'=>'isAdmin check','data'=>['is_admin'=>$__dbgIsAdmin,'roles'=>$__dbgRoles],'timestamp'=>round(microtime(true)*1000)];
                file_put_contents($__dbgLogPath, json_encode($__dbgData2)."\n", FILE_APPEND);
                // #endregion
            @endphp
            @if(Auth::user()?->isAdmin())
                @php
                    $teamsWithoutPeopleManager = \App\Models\Team::whereDoesntHave('managers', function ($q) {
                        $q->whereHas('roles', fn ($r) => $r->where('slug', 'people_manager'));
                    })->pluck('name');

                    // #region agent log
                    $__dbgData3 = ['hypothesisId'=>'H3','sessionId'=>'5c18c4','location'=>'app.blade.php:50','message'=>'Query result','data'=>['count'=>$teamsWithoutPeopleManager->count(),'teams'=>$teamsWithoutPeopleManager->toArray(),'is_not_empty'=>$teamsWithoutPeopleManager->isNotEmpty()],'timestamp'=>round(microtime(true)*1000)];
                    file_put_contents($__dbgLogPath, json_encode($__dbgData3)."\n", FILE_APPEND);
                    // #endregion
                @endphp
                @if($teamsWithoutPeopleManager->isNotEmpty())
                    @php
                        // #region agent log
                        $__dbgData4 = ['hypothesisId'=>'H4','sessionId'=>'5c18c4','location'=>'app.blade.php:55','message'=>'Alert rendered','data'=>['rendered'=>true],'timestamp'=>round(microtime(true)*1000)];
                        file_put_contents($__dbgLogPath, json_encode($__dbgData4)."\n", FILE_APPEND);
                        // #endregion
                    @endphp
                    <div class="px-6 lg:px-8 pt-4">
                        <x-alert type="warning" title="Teams ohne People Manager">
                            {{ $teamsWithoutPeopleManager->count() }} Team(s) ohne zugewiesenen People Manager:
                            <strong>{{ $teamsWithoutPeopleManager->join(', ') }}</strong>.
                            Asana-Tasks für Mitarbeiter dieser Teams werden ohne Zuweisung erstellt.
                            <a href="{{ route('admin.users.index') }}" class="underline font-semibold">Zur Nutzerverwaltung</a>
                        </x-alert>
                    </div>
                @endif
            @else
                @php
                    // #region agent log
                    $__dbgData5 = ['hypothesisId'=>'H2-else','sessionId'=>'5c18c4','location'=>'app.blade.php:68','message'=>'NOT admin branch','data'=>['skipped'=>true],'timestamp'=>round(microtime(true)*1000)];
                    file_put_contents($__dbgLogPath, json_encode($__dbgData5)."\n", FILE_APPEND);
                    // #endregion
                @endphp
            @endif

            <!-- Page Content -->
            <main class="flex-1 p-6 lg:p-8 overflow-auto">
                {{ $slot }}
            </main>
            
            <!-- Footer -->
            @include('layouts.footer')
        </div>
    </div>
    
    <!-- Toast Container -->
    @include('layouts.toasts')

    @livewireScripts
    @stack('scripts')
</body>
</html>
