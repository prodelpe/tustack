<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
    <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-4">
        <x-application-logo />

        {{ $slot }}

        <div class="ml-auto flex items-center gap-3 shrink-0">
            @auth
                <span class="text-sm text-gray-500 dark:text-slate-400">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition">
                        Log out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition">Log in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-indigo-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-600 transition">Register</a>
            @endauth
        </div>
    </div>
</header>
