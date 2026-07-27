@props(['slot' => '', 'showProvinces' => true, 'showConsultancyFilter' => true])

{{--
    One sidebar for every screen size: below lg it slides in as a panel, from lg
    up it is a plain column. The filter widgets mount into #filter-technologies
    and #filter-provinces, so the element must stay in the DOM at all times —
    that is why it moves with a transform instead of being shown and hidden.
--}}
<div x-data="filterPanel" class="lg:w-56 lg:shrink-0">

    <button
        type="button"
        @click="toggle()"
        class="mb-4 flex w-full items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:border-gray-300 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300 lg:hidden"
    >
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M6 12h12M10 20h4"/>
        </svg>
        {{ __('sidebar.filters') }}
        <span x-show="count > 0" x-cloak x-text="'(' + count + ')'" class="text-indigo-600 dark:text-indigo-400"></span>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition.opacity
        @click="close()"
        class="fixed inset-0 z-30 bg-gray-900/40 lg:hidden"
        aria-hidden="true"
    ></div>

    <aside
        :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 left-0 z-40 w-[86%] max-w-xs overflow-y-auto bg-white p-4 shadow-xl transition-transform duration-200 dark:bg-slate-950 lg:static lg:z-auto lg:w-56 lg:max-w-none lg:overflow-visible lg:bg-transparent lg:p-0 lg:shadow-none lg:dark:bg-transparent"
    >
        <div class="mb-4 flex items-center justify-between lg:hidden">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('sidebar.filters') }}</p>
            <button
                type="button"
                @click="close()"
                aria-label="{{ __('sidebar.close_filters') }}"
                class="text-gray-400 transition hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{ $slot }}

        @if($showConsultancyFilter)
        <div class="mb-4 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 space-y-2.5">
            <label class="flex cursor-pointer select-none items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-slate-400">{{ __('sidebar.exclude_consultancies') }}</span>
                <div class="relative">
                    <input type="checkbox" id="exclude-consultancies" class="sr-only peer">
                    <div class="w-8 h-4 rounded-full bg-gray-200 peer-checked:bg-amber-400 dark:bg-slate-700 dark:peer-checked:bg-amber-500 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
                </div>
            </label>
            <label class="flex cursor-pointer select-none items-center justify-between">
                <span class="text-xs text-gray-500 dark:text-slate-400">{{ __('sidebar.exclude_recruitment') }}</span>
                <div class="relative">
                    <input type="checkbox" id="exclude-recruitment" class="sr-only peer">
                    <div class="w-8 h-4 rounded-full bg-gray-200 peer-checked:bg-teal-400 dark:bg-slate-700 dark:peer-checked:bg-teal-500 transition-colors"></div>
                    <div class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
                </div>
            </label>
        </div>
        @endif

        <div class="{{ $showProvinces ? 'mb-4' : '' }} rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('sidebar.technologies') }}</p>
            <div id="filter-technologies" class="lg:min-h-[400px]"></div>
        </div>

        @if($showProvinces)
        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('sidebar.province') }}</p>
            <div id="filter-provinces" class="lg:min-h-[400px]"></div>
        </div>
        @endif

        <button
            type="button"
            @click="close()"
            class="mt-5 w-full rounded-xl bg-indigo-500 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-indigo-600 lg:hidden"
        >{{ __('sidebar.show_results') }}</button>
    </aside>
</div>
