@extends('layouts.app')

@section('content')

<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
    <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-4">
        <a href="{{ route('home') }}" class="shrink-0 text-xl font-bold tracking-tight text-gray-900 dark:text-white">
            Find Your <span class="font-mono text-indigo-500 dark:text-indigo-400">DEV</span> Stack
        </a>
        <div id="searchbox" class="flex-1 max-w-sm"></div>
    </div>
</header>

<main class="mx-auto max-w-6xl px-4 py-8">
    <div class="flex gap-8">

        {{-- Sidebar filtres --}}
        <aside class="w-56 shrink-0">
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
            <div id="stats" class="mb-4 text-sm text-gray-500 dark:text-slate-400"></div>
            <div id="hits"></div>
            <div id="pagination" class="mt-8"></div>
        </div>

    </div>
</main>

@push('scripts')
<script>
    window.__MEILISEARCH_HOST__ = @json($meilisearchHost);
    window.__MEILISEARCH_KEY__  = @json($meilisearchKey);
</script>
@endpush

@endsection
