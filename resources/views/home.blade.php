@extends('layouts.app')

@if($meilisearchAvailable)
@section('header-slot')
<div id="searchbox" class="flex-1 max-w-sm"></div>
@endsection
@endif

@section('content')

@if(!$meilisearchAvailable)
<main class="mx-auto max-w-6xl px-4 py-8">
    <div class="flex min-h-[60vh] flex-col items-center justify-center gap-6 text-center">
        <div class="rounded-2xl border border-red-200 bg-red-50 p-15 dark:border-red-900/40 dark:bg-red-950/30">
            <p class="text-lg font-semibold text-gray-800 dark:text-white">Search is temporarily unavailable</p>
            <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">
                We're working on it. Please try again in a few minutes.
            </p>
        </div>
    </div>
</main>
@else
<main class="mx-auto max-w-6xl px-4 py-8">
    <div class="flex gap-8">

        <x-search-sidebar>
            <p class="mb-6 text-xs text-gray-400 dark:text-slate-500">
                {{ $technologiesCount }} technologies &middot; {{ $provincesCount }} provinces
            </p>
        </x-search-sidebar>

        {{-- Resultats --}}
        <div class="flex-1 min-w-0">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div id="stats" class="text-sm text-gray-500 dark:text-slate-400"></div>
                    <div id="clear-filters"></div>
                    <x-outline-button id="map-link" href="{{ route('map') }}" class="gap-1.5 px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>
                        View map
                    </x-outline-button>
                    <x-outline-button id="trends-link" href="{{ route('tendencies') }}" class="gap-1.5 px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        Trends
                    </x-outline-button>
                    @auth
                    <div
                        x-data="{
                            filters: { technologies: [], provinces: [], query: '' },
                            savedFilters: window.__SAVED_FILTERS__ || [],
                            get hasFilters() {
                                return this.filters.technologies.length > 0 || this.filters.provinces.length > 0 || !!this.filters.query
                            },
                            get isAlreadySaved() {
                                const current = JSON.stringify(this.filters)
                                return this.savedFilters.some(f => JSON.stringify(f) === current)
                            },
                            async save() {
                                const res = await fetch('{{ route('saved-searches.store') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                    body: JSON.stringify(this.filters)
                                })
                                if (res.ok) this.savedFilters.push(JSON.parse(JSON.stringify(this.filters)))
                            }
                        }"
                        @search-updated.window="filters = $event.detail"
                    >
                        <button
                            x-show="hasFilters && !isAlreadySaved"
                            x-cloak
                            @click="save"
                            class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >Create alert for this search</button>
                        <span
                            x-show="hasFilters && isAlreadySaved"
                            x-cloak
                            class="text-xs text-green-600 dark:text-green-400"
                        >✓ Search saved</span>
                    </div>
                    @endauth
                </div>
                <div id="sort-by" class="shrink-0"></div>
            </div>
            <div id="salary-insights" class="hidden mb-4"></div>
            <div id="hits"></div>
            <div id="pagination" class="mt-8"></div>
        </div>

    </div>
</main>
@endif

@if($meilisearchAvailable)
@push('scripts')
<script>
    window.__MEILISEARCH_HOST__    = @json($meilisearchHost);
    window.__MEILISEARCH_KEY__     = @json($meilisearchKey);
    window.__SAVED_FILTERS__       = @json($savedFilters);
    window.__MAP_URL__             = @json(route('map'));
    window.__TENDENCIES_URL__      = @json(route('tendencies'));
    window.__TRACK_SEARCH_URL__    = @json(route('track-search'));
    window.__SALARY_INSIGHTS_URL__ = @json(route('salary-insights'));
    window.__IS_ADMIN__            = @json(auth()->check() && auth()->user()->is_admin);
</script>
@vite('resources/js/search.js')
@endpush
@endif

@endsection
