<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'td Academy') }} - Login</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-50 font-sans antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        {{-- Brand Header --}}
        <div class="mb-6 text-center">
            <a href="/" class="inline-flex items-center gap-3">
                <div class="w-12 h-12 rounded-xl bg-brand-primary flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
                <div>
                    <div class="text-2xl font-bold text-brand-dark">td Academy</div>
                    <div class="text-xs text-surface-500">trafficdesign Lernplattform</div>
                </div>
            </a>
        </div>

        {{-- Card --}}
        <div class="w-full sm:max-w-md card-tool p-6 sm:p-8">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <div class="mt-6 text-xs text-surface-400">
            &copy; {{ date('Y') }} trafficdesign GmbH
        </div>
    </div>
</body>
</html>
