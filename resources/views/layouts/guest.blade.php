<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'td Academy') }} - Login</title>

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="180x180" href="{{ asset('favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface-50 font-sans antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
        {{-- Brand Header --}}
        <div class="mb-6 text-center">
            <a href="/">
                <img src="{{ asset('images/logo-color.png') }}" alt="trafficdesign Academy" class="h-14 mx-auto">
            </a>
        </div>

        {{-- Card --}}
        <div class="w-full sm:max-w-md card-tool p-8 sm:p-10">
            {{ $slot }}
        </div>

        {{-- Footer --}}
        <div class="mt-6 text-xs text-surface-400">
            &copy; {{ date('Y') }} trafficdesign GmbH
        </div>
    </div>
</body>
</html>
