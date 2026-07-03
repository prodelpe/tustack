<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TuStack</title>
    @if(env('APP_NOINDEX'))
    <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
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
