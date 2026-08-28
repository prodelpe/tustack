@props(['item', 'max'])

@php
    $technology = $item['technology'];
    $share      = $max > 0 ? max(3, (int) round(100 * $item['offers'] / $max)) : 0;
@endphp

<div class="flex items-center gap-3">
    <div class="w-24 shrink-0 truncate text-sm font-medium text-gray-900 sm:w-36 dark:text-white">
        {{ $technology->name }}
    </div>

    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-slate-800">
        <div class="h-2 rounded-full bg-indigo-500/70 dark:bg-indigo-400/60" style="width: {{ $share }}%"></div>
    </div>

    <div class="w-28 shrink-0 text-right text-xs tabular-nums text-gray-400 sm:w-40 dark:text-slate-500">
        {{ $item['offers'] }} {{ $item['offers'] === 1 ? __('company.offer') : __('company.offers') }}
        @if ($item['last_offer_at'])
            <span class="hidden sm:inline">· {{ $item['last_offer_at']->translatedFormat('M Y') }}</span>
        @endif
    </div>
</div>
