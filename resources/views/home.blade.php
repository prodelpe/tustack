@extends('layouts.app')

@section('content')

<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
    <div class="mx-auto flex max-w-6xl items-center gap-4 px-4 py-4">
        <x-application-logo />
        @if($meilisearchAvailable)
        <div id="searchbox" class="flex-1 max-w-sm"></div>
        @endif

        <div class="ml-auto flex items-center gap-3 shrink-0">
            @auth
                <span class="text-sm text-gray-500 dark:text-slate-400">{{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition">
                        Log out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-white transition">Log in</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-indigo-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-600 transition">Register</a>
            @endauth
        </div>
    </div>
</header>

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
            <div class="mb-4 flex items-center justify-between">
                <div id="stats" class="text-sm text-gray-500 dark:text-slate-400"></div>
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
