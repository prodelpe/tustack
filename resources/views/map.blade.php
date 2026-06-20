@extends('layouts.app')

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
        <div class="flex gap-8">

            <x-search-sidebar>
                <p class="mb-6 text-xs text-gray-400 dark:text-slate-500">
                    <span id="stats"></span>
                </p>
            </x-search-sidebar>

            {{-- Mapa --}}
            <div class="flex-1 min-w-0">
                <div class="mb-4 flex items-center gap-4">
                    <a id="list-link" href="{{ route('home') }}" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition">
                        ← Back to list
                    </a>
                    <div id="clear-filters"></div>
                </div>
                <div id="map" class="w-full rounded-2xl border border-gray-200 dark:border-slate-800 overflow-hidden" style="height: 600px;"></div>
            </div>

        </div>
    @endif
</main>

@push('scripts')
<script>
    window.__MEILISEARCH_HOST__ = @json($meilisearchHost);
    window.__MEILISEARCH_KEY__  = @json($meilisearchKey);
    window.__HOME_URL__         = @json(route('home'));
</script>
@vite('resources/js/map.js')
@endpush
@endsection
