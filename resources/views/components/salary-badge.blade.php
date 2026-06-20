@props(['salary'])

<div class="inline-flex items-center gap-3 rounded-lg border border-green-200 bg-green-50 px-4 py-2 dark:border-green-800/40 dark:bg-green-950/30">
    <svg class="w-4 h-4 text-green-600 dark:text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <div>
        <span class="text-sm font-semibold text-green-700 dark:text-green-400">
            ~{{ round($salary['avg_salary'] / 1000) }}k €
        </span>
        <span class="ml-1 text-xs text-gray-500 dark:text-slate-400">avg. salary</span>
        <span class="ml-2 text-xs text-gray-400 dark:text-slate-500">
            {{ round($salary['range_min'] / 1000) }}k – {{ round($salary['range_max'] / 1000) }}k € range · {{ $salary['offers_count'] }} {{ Str::plural('offer', $salary['offers_count']) }} with salary data
        </span>
    </div>
</div>
