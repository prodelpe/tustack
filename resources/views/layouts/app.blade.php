<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @include('partials.seo')
    @stack('schema')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.analytics')
</head>
<body x-data="themeToggle" class="h-full bg-white text-gray-900 antialiased dark:bg-slate-950 dark:text-white">

    <div x-data="{ searchOpen: false }">
        <x-app-header>@yield('header-slot')</x-app-header>
        @yield('search-bar')
    </div>

    @yield('content')

    <x-app-footer />

    @stack('scripts')
</body>
</html>
