@extends('layouts.app')

@section('content')

<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
    <div class="mx-auto flex max-w-5xl items-center gap-4 px-4 py-4">
        <a href="{{ route('home') }}" class="shrink-0 text-xl font-bold tracking-tight text-gray-900 dark:text-white">
            Find Your <span class="font-mono text-indigo-500 dark:text-indigo-400">DEV</span> Stack
        </a>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-10">

    <a href="javascript:history.back()"
        class="mb-8 inline-flex items-center gap-2 text-sm text-gray-500 transition hover:text-gray-900 dark:text-slate-400 dark:hover:text-white">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back
    </a>

    <div class="mb-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $company->name }}</h1>
                @if ($company->city || $company->province)
                    <p class="mt-1 text-gray-500 dark:text-slate-400">
                        <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ collect([$company->city, $company->province?->name])->filter()->unique()->implode(', ') }}
                    </p>
                @endif
            </div>
            <span class="shrink-0 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
                {{ $jobOffers->count() }} {{ Str::plural('position', $jobOffers->count()) }}
            </span>
        </div>

        @if ($technologies->isNotEmpty())
            <div class="mt-5">
                <p class="mb-2 text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-slate-500">Tech stack</p>
                <div class="flex flex-wrap gap-2">
                    @foreach ($technologies as $tech)
                        <span class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
                            {{ $tech->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div>
        <p class="mb-4 text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-slate-500">Open positions</p>

        @forelse ($jobOffers as $offer)
            <a href="{{ $offer->url }}" target="_blank" rel="noopener noreferrer"
                class="group mb-3 flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-4 transition hover:border-gray-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700 dark:hover:bg-slate-800/50"
            >
                <div>
                    <p class="font-medium text-gray-900 transition group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-300">{{ $offer->title }}</p>
                    <div class="mt-1 flex items-center gap-3 text-xs text-gray-400 dark:text-slate-500">
                        @if ($offer->published_at)
                            <span>{{ $offer->published_at->format('d M Y') }}</span>
                        @endif
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 dark:bg-slate-800">{{ $offer->source }}</span>
                        @if ($offer->salary_min || $offer->salary_max)
                            <span class="text-green-600 dark:text-green-400">
                                {{ $offer->salary_min ? $offer->salary_min . 'k' : '' }}
                                {{ $offer->salary_min && $offer->salary_max ? '–' : '' }}
                                {{ $offer->salary_max ? $offer->salary_max . 'k' : '' }} €
                            </span>
                        @endif
                    </div>
                </div>
                <svg class="w-4 h-4 shrink-0 text-gray-300 transition group-hover:text-indigo-500 dark:text-slate-600 dark:group-hover:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        @empty
            <p class="text-gray-500 dark:text-slate-400">No positions available.</p>
        @endforelse
    </div>

</main>
@endsection
