@extends('layouts.app')

@section('title', __('seo.map_title'))
@section('description', __('seo.map_description'))

@section('content')
<main class="mx-auto max-w-6xl px-4 py-8">
    @if(!$meilisearchAvailable)
        <div class="flex min-h-[60vh] flex-col items-center justify-center gap-6 text-center">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-15 dark:border-red-900/40 dark:bg-red-950/30">
                <p class="text-lg font-semibold text-gray-800 dark:text-white">Map is temporarily unavailable</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">We're working on it. Please try again in a few minutes.</p>
            </div>
        </div>
    @else
        <div class="lg:flex lg:gap-8">

            <x-search-sidebar />

            {{-- Mapa --}}
            <div class="min-w-0 flex-1">
                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <x-back-to-list-link />
                    {{-- Outside the sidebar on purpose: inside it, the count would
                         be hidden in the filter panel on small screens. --}}
                    <span id="stats" class="text-xs text-gray-400 dark:text-slate-500"></span>
                    <div id="clear-filters"></div>
                </div>
                <div id="map" class="h-[60vh] min-h-[380px] w-full overflow-hidden rounded-2xl border border-gray-200 dark:border-slate-800 lg:h-[600px]"></div>
            </div>

        </div>
    @endif
</main>

@push('scripts')
<script>
    window.__MEILISEARCH_HOST__ = @json($meilisearchHost);
    window.__MEILISEARCH_KEY__  = @json($meilisearchKey);
    window.__HOME_URL__         = @json(route('home'));
    window.__COMPANY_URL__      = @json(route('companies.show', ['company' => '__SLUG__']));
</script>
@vite('resources/js/map.js')
@endpush
@endsection
