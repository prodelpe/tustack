@props(['slot' => ''])

<aside class="w-56 shrink-0">
    {{ $slot }}
    <div class="mb-4 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
        <label class="flex cursor-pointer select-none items-center justify-between">
            <span class="text-xs text-gray-500 dark:text-slate-400">Exclude consultancies</span>
            <div class="relative">
                <input type="checkbox" id="exclude-consultancies" class="sr-only peer">
                <div class="w-8 h-4 rounded-full bg-gray-200 peer-checked:bg-indigo-500 dark:bg-slate-700 dark:peer-checked:bg-indigo-500 transition-colors"></div>
                <div class="absolute top-0.5 left-0.5 w-3 h-3 rounded-full bg-white shadow transition-transform peer-checked:translate-x-4"></div>
            </div>
        </label>
    </div>
    <div class="mb-4 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Technologies</p>
        <div id="filter-technologies"></div>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900">
        <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Province</p>
        <div id="filter-provinces"></div>
    </div>
    <div class="mt-4" id="clear-filters"></div>
</aside>
