@props(['offer'])

<a href="{{ route('companies.offers.show', [$offer->company_id, $offer]) }}"
    class="group mb-3 flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-4 transition hover:border-gray-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-800/50"
>
    <div>
        <p class="font-medium text-gray-900 transition group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-300">{{ $offer->title }}</p>
        <div class="mt-1 flex items-center gap-3 text-xs text-gray-400 dark:text-slate-500">
            @if ($offer->published_at)
                <span>{{ $offer->published_at->format('d M Y') }}</span>
            @endif
            <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-slate-800">{{ $offer->source }}</span>
            @if ($offer->salary_min || $offer->salary_max)
                <span class="text-green-600 dark:text-green-400">
                    {{ $offer->salary_min ? $offer->salary_min . 'k' : '' }}
                    {{ $offer->salary_min && $offer->salary_max ? '–' : '' }}
                    {{ $offer->salary_max ? $offer->salary_max . 'k' : '' }} €
                </span>
            @endif
        </div>
    </div>
    <svg class="w-4 h-4 shrink-0 text-gray-300 transition group-hover:text-indigo-500 dark:text-slate-600 dark:group-hover:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
</a>
