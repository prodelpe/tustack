<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
    <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-4">
        <x-application-logo />

        {{ $slot }}

        <div class="ml-auto flex items-center gap-3 shrink-0">
            <div class="flex items-center gap-0.5 rounded-full border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800 p-1 shadow-sm">
                @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                    <a href="{{ LaravelLocalization::getLocalizedURL($localeCode) }}"
                       class="rounded-full px-2 py-0.5 text-xs font-medium transition {{ app()->getLocale() === $localeCode ? 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300' }}">
                        {{ strtoupper($localeCode) }}
                    </a>
                @endforeach
            </div>
            @auth
                <a href="{{ route('dashboard') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition">{{ Auth::user()->name }}</a>
                <form method="POST" action="{{ route('logout') }}" class="flex items-center">
                    @csrf
                    <button type="submit" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-white transition">
                        {{ __('nav.logout') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-white transition">{{ __('nav.login') }}</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-indigo-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-600 transition">{{ __('nav.register') }}</a>
            @endauth
        </div>
    </div>
</header>
