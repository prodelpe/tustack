<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
    <div x-data="navMenu" class="mx-auto max-w-6xl px-4">

        <div class="flex items-center gap-2 py-3 sm:gap-4 sm:py-4">
            <x-application-logo />

            {{ $slot }}

            {{-- Full navigation, from sm up --}}
            <div class="ml-auto hidden shrink-0 items-center gap-3 sm:flex">
                <x-locale-switcher />

                @auth
                    <a href="{{ route('dashboard') }}" class="max-w-[8rem] truncate text-sm text-gray-500 transition hover:text-gray-700 dark:text-slate-400 dark:hover:text-white">{{ Auth::user()->name }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="flex items-center">
                        @csrf
                        <button type="submit" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-500 transition hover:border-gray-300 hover:text-gray-700 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-white">
                            {{ __('nav.logout') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm text-gray-500 transition hover:border-gray-300 hover:text-gray-700 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-white">{{ __('nav.login') }}</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-indigo-500 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-indigo-600">{{ __('nav.register') }}</a>
                @endauth
            </div>

            {{-- Menu button, below sm --}}
            <button
                type="button"
                @click="toggle()"
                :aria-expanded="open"
                aria-label="{{ __('nav.menu') }}"
                class="ml-auto shrink-0 text-gray-500 transition hover:text-gray-700 dark:text-slate-400 dark:hover:text-white sm:hidden"
            >
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/>
                </svg>
            </button>
        </div>

        {{-- Slides in from the right, mirroring the filters panel on the left so
             navigation and filtering never come from the same edge. The links are
             duplicated rather than moved because they carry no state, unlike the
             search facets, which must exist exactly once on the page. --}}
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            @click="close()"
            class="fixed inset-0 z-30 bg-gray-900/40 sm:hidden"
            aria-hidden="true"
        ></div>

        <div
            :class="open ? 'translate-x-0' : 'translate-x-full'"
            class="fixed inset-y-0 right-0 z-40 w-[78%] max-w-xs space-y-3 overflow-y-auto bg-white p-4 shadow-xl transition-transform duration-200 dark:bg-slate-950 sm:hidden"
        >
            <div class="mb-2 flex items-center justify-between">
                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('nav.menu') }}</p>
                <button
                    type="button"
                    @click="close()"
                    aria-label="{{ __('nav.close_menu') }}"
                    class="text-gray-400 transition hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            @auth
                <a href="{{ route('dashboard') }}" class="block rounded-lg border border-gray-200 px-4 py-2.5 text-center text-sm text-gray-600 dark:border-slate-800 dark:text-slate-300">{{ __('nav.dashboard') }}</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg border border-gray-200 px-4 py-2.5 text-sm text-gray-500 dark:border-slate-800 dark:text-slate-400">
                        {{ __('nav.logout') }}
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block rounded-lg border border-gray-200 px-4 py-2.5 text-center text-sm text-gray-600 dark:border-slate-800 dark:text-slate-300">{{ __('nav.login') }}</a>
                <a href="{{ route('register') }}" class="block rounded-lg bg-indigo-500 px-4 py-2.5 text-center text-sm font-medium text-white">{{ __('nav.register') }}</a>
            @endauth

            <div class="border-t border-gray-100 pt-4 dark:border-slate-800">
                <x-locale-switcher class="flex items-center gap-0.5 rounded-full border border-gray-200 bg-white p-1 dark:border-slate-700 dark:bg-slate-800" />
            </div>
        </div>
    </div>
</header>
