@extends('layouts.app')

@section('content')

<main class="mx-auto max-w-6xl px-4 py-10">

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
            <div class="flex items-center gap-3 shrink-0">
                <span class="rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
                    {{ $jobOffers->count() }} {{ Str::plural('position', $jobOffers->count()) }}
                </span>

                @auth
                <div
                    x-data="{
                        saved: {{ $isSaved ? 'true' : 'false' }},
                        async toggle() {
                            const res = await fetch('{{ route('companies.save', $company) }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json',
                                },
                            })
                            const data = await res.json()
                            this.saved = data.saved
                        }
                    }"
                >
                    <button
                        @click="toggle"
                        :title="saved ? 'Remove from My companies' : 'Save to My companies'"
                        class="rounded-lg border border-gray-200 bg-white p-2 transition hover:border-gray-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-slate-600"
                    >
                        {{-- Outline: not saved --}}
                        <svg x-show="!saved" class="w-4 h-4 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                        {{-- Filled: saved --}}
                        <svg x-show="saved" class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                        </svg>
                    </button>
                </div>
                @endauth
            </div>
        </div>

        @if ($company->description)
            @php
                $desc = $company->description[app()->getLocale()]
                    ?? $company->description['es']
                    ?? $company->description['en']
                    ?? collect($company->description)->filter()->first();
            @endphp
            @if ($desc)
                <p class="mt-4 text-sm text-gray-600 dark:text-slate-400 max-w-2xl">{{ $desc }}</p>
            @endif
        @endif

        @if ($company->sector || $company->employees || $company->website)
            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-gray-400 dark:text-slate-500">
                @if ($company->sector)
                    @php $sector = $company->sector[app()->getLocale()] ?? $company->sector['es'] ?? $company->sector['en'] ?? collect($company->sector)->filter()->first(); @endphp
                    @if ($sector)
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            {{ $sector }}
                        </span>
                    @endif
                @endif
                @if ($company->employees)
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ $company->employees }} employees
                    </span>
                @endif
                @if ($company->website)
                    <a href="{{ $company->website }}" target="_blank" rel="noopener noreferrer"
                        class="flex items-center gap-1 hover:text-indigo-500 dark:hover:text-indigo-400 transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        {{ parse_url($company->website, PHP_URL_HOST) }}
                    </a>
                @endif
            </div>
        @endif

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
        <p class="mb-4 text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-slate-500">
            Job offers · {{ $jobOffers->total() }}
        </p>

        @forelse ($jobOffers as $offer)
            <x-job-offer-row :offer="$offer" />
        @empty
            <p class="text-gray-500 dark:text-slate-400">No positions available.</p>
        @endforelse

        @if ($jobOffers->hasPages())
            <div class="mt-6">
                {{ $jobOffers->links('pagination::simple-tailwind') }}
            </div>
        @endif
    </div>

</main>

@endsection
