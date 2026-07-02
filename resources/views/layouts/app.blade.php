<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TuStack</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body x-data="themeToggle" class="h-full bg-white text-gray-900 antialiased dark:bg-slate-950 dark:text-white">

    {{-- Theme toggle --}}
    <div class="fixed top-4 right-4 z-50 flex items-center gap-1 rounded-full border border-gray-200 bg-white/80 p-1 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-900/80">
        {{-- System --}}
        <button
            type="button"
            @click="setTheme('system')"
            :class="theme === 'system' ? 'bg-gray-200 text-gray-900 dark:bg-slate-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300'"
            class="rounded-full p-1.5 transition"
            title="System"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
            </svg>
        </button>
        {{-- Light --}}
        <button
            type="button"
            @click="setTheme('light')"
            :class="theme === 'light' ? 'bg-gray-200 text-gray-900 dark:bg-slate-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300'"
            class="rounded-full p-1.5 transition"
            title="Light"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
            </svg>
        </button>
        {{-- Dark --}}
        <button
            type="button"
            @click="setTheme('dark')"
            :class="theme === 'dark' ? 'bg-gray-200 text-gray-900 dark:bg-slate-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300'"
            class="rounded-full p-1.5 transition"
            title="Dark"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>
    </div>

    <x-app-header>@yield('header-slot')</x-app-header>

    @yield('content')

    <x-app-footer />

    @stack('scripts')
</body>
</html>
