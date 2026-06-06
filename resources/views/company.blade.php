@extends('layouts.app')

@section('content')

<header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur sticky top-0 z-10">
    <div class="mx-auto flex max-w-5xl items-center gap-6 px-4 py-4">
        <a href="{{ route('home') }}" class="shrink-0 text-xl font-bold tracking-tight text-white">
            Find Your <span class="text-indigo-400">Stack</span>
        </a>
        <form action="{{ route('search') }}" method="GET" class="flex-1">
            <div class="flex overflow-hidden rounded-xl border border-slate-700 bg-slate-900 focus-within:border-indigo-500 transition-all">
                <input
                    type="text"
                    name="q"
                    placeholder="Laravel, Vue, React…"
                    class="flex-1 bg-transparent px-4 py-2.5 text-sm text-white placeholder-slate-500 outline-none"
                >
                <button type="submit" class="m-1 rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                    Search
                </button>
            </div>
        </form>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-10">

    {{-- Back --}}
    <a href="javascript:history.back()" class="mb-8 inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back
    </a>

    {{-- Company header --}}
    <div class="mb-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white">{{ $company->name }}</h1>
                @if ($company->province || $company->location)
                    <p class="mt-1 text-slate-400">
                        <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $company->province ?? $company->location }}
                    </p>
                @endif
            </div>
            <span class="shrink-0 rounded-full bg-indigo-500/10 border border-indigo-500/20 px-4 py-1.5 text-sm text-indigo-300">
                {{ $jobOffers->count() }} {{ Str::plural('position', $jobOffers->count()) }}
            </span>
        </div>

        {{-- Tech stack --}}
        @if ($technologies->isNotEmpty())
            <div class="mt-5">
                <p class="mb-2 text-xs font-medium uppercase tracking-widest text-slate-500">Tech stack</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($technologies as $tech)
                        <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-sm font-medium text-indigo-300">
                            {{ $tech->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Job offers --}}
    <div>
        <p class="mb-4 text-xs font-medium uppercase tracking-widest text-slate-500">Open positions</p>

        @forelse ($jobOffers as $offer)
            <a
                href="{{ $offer->url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="mb-3 flex items-center justify-between rounded-xl border border-slate-800 bg-slate-900 px-5 py-4 transition hover:border-slate-700 hover:bg-slate-800/50 group"
            >
                <div>
                    <p class="font-medium text-white group-hover:text-indigo-300 transition">{{ $offer->title }}</p>
                    <div class="mt-1 flex items-center gap-3 text-xs text-slate-500">
                        @if ($offer->published_at)
                            <span>{{ $offer->published_at->format('d M Y') }}</span>
                        @endif
                        <span class="rounded-full bg-slate-800 px-2 py-0.5">{{ $offer->source }}</span>
                    </div>
                </div>
                <svg class="w-4 h-4 text-slate-600 group-hover:text-indigo-400 transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        @empty
            <p class="text-slate-400">No positions available.</p>
        @endforelse
    </div>

</main>
@endsection
