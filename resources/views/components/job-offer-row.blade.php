@props(['offer'])

@php
    $isRecent = $offer->published_at && $offer->published_at->diffInDays(now()) <= 60;
@endphp

<div class="mb-3 rounded-xl border border-gray-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="font-medium text-gray-900 dark:text-white">{{ $offer->title }}</p>
            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-gray-400 dark:text-slate-500">
                @if ($offer->published_at)
                    <span>{{ $offer->published_at->format('d M Y') }}</span>
                @endif
                <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-slate-800">{{ $offer->source }}</span>
                @if ($offer->salary_min || $offer->salary_max)
                    <span class="text-green-600 dark:text-green-400">
                        {{ $offer->salary_min ? round($offer->salary_min / 1000) . 'k' : '' }}
                        {{ $offer->salary_min && $offer->salary_max ? '–' : '' }}
                        {{ $offer->salary_max ? round($offer->salary_max / 1000) . 'k' : '' }} €
                    </span>
                @endif
            </div>
            @if ($offer->description)
                <p class="mt-2 text-sm text-gray-500 line-clamp-2 dark:text-slate-400">{{ html_entity_decode(strip_tags($offer->description), ENT_QUOTES | ENT_HTML5) }}</p>
            @endif
        </div>
        @if ($isRecent)
            <a href="{{ $offer->url }}" target="_blank" rel="noopener noreferrer"
                title="Apply on {{ ucfirst($offer->source) }}"
                class="shrink-0 rounded-lg border border-gray-200 bg-white p-2 text-gray-400 transition hover:border-gray-300 hover:text-indigo-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-500 dark:hover:border-slate-600 dark:hover:text-indigo-400">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        @endif
    </div>
</div>
