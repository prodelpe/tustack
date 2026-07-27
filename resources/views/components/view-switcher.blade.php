@props(['current' => 'list'])

@php
    $views = [
        'list'   => ['id' => 'list-link',   'url' => route('home'),       'label' => __('nav.view_list')],
        'map'    => ['id' => 'map-link',    'url' => route('map'),        'label' => __('nav.view_map')],
        'trends' => ['id' => 'trends-link', 'url' => route('tendencies'), 'label' => __('nav.view_trends')],
    ];
@endphp

{{--
    Three ways of looking at the same search, not general navigation — which is
    why it sits next to the results. The ids are the ones the search scripts
    rewrite on every render so each link carries the active filters.
--}}
<div class="mb-4 grid grid-cols-3 gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1 text-center dark:border-slate-800 dark:bg-slate-900/60 sm:inline-grid">
    @foreach($views as $key => $view)
        @if($key === $current)
            <span
                aria-current="page"
                class="rounded-lg bg-white px-4 py-1.5 text-xs font-medium text-gray-900 shadow-sm dark:bg-slate-800 dark:text-white"
            >{{ $view['label'] }}</span>
        @else
            <a
                id="{{ $view['id'] }}"
                href="{{ $view['url'] }}"
                class="rounded-lg px-4 py-1.5 text-xs font-medium text-gray-500 transition hover:text-gray-800 dark:text-slate-400 dark:hover:text-white"
            >{{ $view['label'] }}</a>
        @endif
    @endforeach
</div>
