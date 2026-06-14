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

        {{-- Sidebar filtres --}}
        <aside class="w-56 shrink-0">
            <p class="mb-6 text-xs text-gray-400 dark:text-slate-500">
                {{ $technologiesCount }} technologies &middot; {{ $provincesCount }} provinces
            </p>
            <div class="mb-6">
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Technologies</p>
                <div id="filter-technologies"></div>
            </div>
            <div>
                <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">Province</p>
                <div id="filter-provinces"></div>
            </div>
        </aside>

        {{-- Resultats --}}
        <div class="flex-1 min-w-0">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <div id="stats" class="text-sm text-gray-500 dark:text-slate-400"></div>
                    <label class="flex cursor-pointer select-none items-center gap-2">
                        <input type="checkbox" id="exclude-consultancies"
                               class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-800 dark:focus:ring-indigo-600">
                        <span class="text-sm text-gray-500 dark:text-slate-400">Exclude consultancies</span>
                    </label>
                    @auth
                    <div
                        x-data="{
                            saved: false,
                            filters: { technologies: [], provinces: [], query: '' },
                            get hasFilters() {
                                return this.filters.technologies.length > 0 || this.filters.provinces.length > 0 || !!this.filters.query
                            },
                            async save() {
                                const res = await fetch('{{ route('saved-searches.store') }}', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                    body: JSON.stringify(this.filters)
                                })
                                if (res.ok) this.saved = true
                            }
                        }"
                        @search-updated.window="filters = $event.detail; saved = false"
                    >
                        <button
                            x-show="hasFilters && !saved"
                            x-cloak
                            @click="save"
                            class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >Create alert for this search</button>
                        <span x-show="saved" x-cloak class="text-xs text-green-600 dark:text-green-400">✓ Alert created</span>
                    </div>
                    @endauth
                </div>
                <div id="clear-filters"></div>
            </div>
            <div id="hits"></div>
            <div id="pagination" class="mt-8"></div>
        </div>

    </div>
</main>
@endif

@if($meilisearchAvailable)
@push('scripts')
<script>
    window.__MEILISEARCH_HOST__ = @json($meilisearchHost);
    window.__MEILISEARCH_KEY__  = @json($meilisearchKey);
</script>
@vite('resources/js/search.js')
@endpush
@endif

@endsection
