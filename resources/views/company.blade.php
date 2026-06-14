@extends('layouts.app')

@section('content')

<main class="mx-auto max-w-5xl px-4 py-10">

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
                        <x-tech-badge>{{ $tech->name }}</x-tech-badge>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div>
        <p class="mb-4 text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-slate-500">Open positions</p>

        @forelse ($jobOffers as $offer)
            <x-job-offer-row :offer="$offer" />
        @empty
            <p class="text-gray-500 dark:text-slate-400">No positions available.</p>
        @endforelse
    </div>

</main>

@endsection
