<footer class="border-t border-gray-200 dark:border-slate-800 mt-16">
    <div class="mx-auto max-w-6xl px-4 py-6 flex items-center justify-between text-xs text-gray-400 dark:text-slate-500">
        <div class="flex items-center gap-1 rounded-full border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800 p-1 shadow-sm">
            {{-- System --}}
            <button
                type="button"
                @click="setTheme('system')"
                :class="theme === 'system' ? 'bg-gray-100 text-gray-900 dark:bg-slate-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300'"
                class="rounded-full p-1.5 transition"
                title="{{ __('nav.theme_system') }}"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
            </button>
            {{-- Light --}}
            <button
                type="button"
                @click="setTheme('light')"
                :class="theme === 'light' ? 'bg-gray-100 text-gray-900 dark:bg-slate-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300'"
                class="rounded-full p-1.5 transition"
                title="{{ __('nav.theme_light') }}"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M17.657 17.657l-.707-.707M6.343 6.343l-.707-.707M12 7a5 5 0 100 10A5 5 0 0012 7z"/>
                </svg>
            </button>
            {{-- Dark --}}
            <button
                type="button"
                @click="setTheme('dark')"
                :class="theme === 'dark' ? 'bg-gray-100 text-gray-900 dark:bg-slate-700 dark:text-white' : 'text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300'"
                class="rounded-full p-1.5 transition"
                title="{{ __('nav.theme_dark') }}"
            >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
            </button>
        </div>

        <span>© {{ date('Y') }} TuStack by <a href="https://prodelpe.com" target="_blank" rel="noopener" class="hover:text-gray-600 dark:hover:text-slate-300 transition">prodelpe.com</a></span>
    </div>

    <div class="mx-auto max-w-6xl px-4 pb-6 flex flex-wrap justify-center gap-x-4 gap-y-2 text-xs text-gray-400 dark:text-slate-500">
        @foreach (App\Support\LegalDocument::DOCUMENTS as $document)
            <a href="{{ route('legal.' . $document) }}" class="hover:text-gray-600 dark:hover:text-slate-300 transition">
                {{ __('legal.' . $document . '_heading') }}
            </a>
        @endforeach
    </div>
</footer>
