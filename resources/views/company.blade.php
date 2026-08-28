@extends('layouts.app')

@section('title', $seoTitle)
@section('description', $seoDescription)

@push('schema')
<x-json-ld :data="$schema" />
@endpush

@section('content')

<main class="mx-auto max-w-6xl px-4 py-10">

    <x-breadcrumbs :trail="$breadcrumbs" />

    <div class="mb-8">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $company->display_name }}</h1>
                    @if ($company->sector === 'it_consulting')
                        <x-company-type-badge type="consultancy" />
                    @elseif ($company->sector === 'recruitment')
                        <x-company-type-badge type="recruitment" />
                    @endif
                </div>
                @if ($company->city || $company->province)
                    <p class="mt-1 text-gray-500 dark:text-slate-400">
                        <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ collect([$company->city, $company->province?->name])->filter()->unique()->implode(', ') }}
                    </p>
                @endif
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <span class="rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
                    @if ($activeOffers)
                        {{ $activeOffers }} {{ $activeOffers === 1 ? __('home.offer_active') : __('home.offers_active') }} ·
                    @endif
                    {{ $jobOffers->total() }} {{ __('home.offers_historic') }}
                </span>

                @auth
                    <x-bookmark-button :company="$company" :is-saved="$isSaved" />
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
                @if ($company->sector && $sectorLabel = config("sectors.{$company->sector}." . app()->getLocale()) ?? config("sectors.{$company->sector}.es"))
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        {{ $sectorLabel }}
                    </span>
                @endif
                @if ($company->employees)
                    <span class="flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        {{ __('company.employees', ['count' => $company->employees]) }}
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

        @php
            $visible = $stack->take(8);
            $folded  = $stack->skip(8);
            $busiest = $stack->max('offers') ?? 0;
        @endphp

        <div class="mt-8">
            <p class="text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-slate-500">
                {{ $company->sector === 'recruitment' ? __('company.tech_stack_recruiter') : __('company.tech_stack') }}
            </p>

            @if ($stack->isNotEmpty())
                <p class="mb-4 mt-1 text-xs text-gray-400 dark:text-slate-500">{{ __('company.stack_hint') }}</p>

                <div class="space-y-2">
                    @foreach ($visible as $item)
                        <x-technology-row :item="$item" :max="$busiest" />
                    @endforeach
                </div>

                @if ($folded->isNotEmpty())
                    <details class="mt-3">
                        <summary class="cursor-pointer text-xs text-gray-500 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400">
                            {{ __('company.more_technologies', ['count' => $folded->count()]) }}
                        </summary>
                        <div class="mt-2 space-y-2">
                            @foreach ($folded as $item)
                                <x-technology-row :item="$item" :max="$busiest" />
                            @endforeach
                        </div>
                    </details>
                @endif
            @else
                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">{{ __('company.no_stack') }}</p>
            @endif
        </div>

        @if ($salary)
            <div class="mt-4">
                <x-salary-badge :salary="$salary" />
            </div>
        @endif
    </div>

    <div class="mt-12 border-t border-gray-100 pt-8 dark:border-slate-800">
        <p class="mb-4 text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-slate-500">
            {{ __('company.job_offers') }} · {{ $jobOffers->total() }}
        </p>

        @forelse ($jobOffers as $offer)
            <x-job-offer-row :offer="$offer" />
        @empty
            <p class="text-gray-500 dark:text-slate-400">{{ __('company.no_positions') }}</p>
        @endforelse

        @if ($jobOffers->hasPages())
            <div class="mt-6">
                {{ $jobOffers->links('pagination::simple-tailwind') }}
            </div>
        @endif
    </div>

    @if ($similarCompanies->isNotEmpty())
        <div class="mt-12">
            <p class="mb-4 text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('company.similar_companies') }}</p>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($similarCompanies as $similar)
                    <a href="{{ route('companies.show', $similar) }}"
                        class="group flex flex-col rounded-xl border border-gray-200 bg-white px-5 py-4 transition hover:border-gray-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="font-medium text-gray-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-300">
                                {{ $similar->display_name }}
                            </p>
                            @if ($similar->sector === 'it_consulting')
                                <x-company-type-badge type="consultancy" />
                            @elseif ($similar->sector === 'recruitment')
                                <x-company-type-badge type="recruitment" />
                            @endif
                        </div>
                        @if ($similar->city || $similar->province)
                            <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">
                                {{ collect([$similar->city, $similar->province?->name])->filter()->unique()->implode(', ') }}
                            </p>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    @if (! empty($landings))
        <div class="mt-14 border-t border-gray-100 pt-8 dark:border-slate-800">
            <p class="mb-5 text-xs font-medium uppercase tracking-widest text-gray-400 dark:text-slate-500">{{ __('landing.company_landings') }}</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($landings as $landing)
                    <a
                        href="{{ $landing['url'] }}"
                        class="rounded-full border border-gray-200 px-3 py-1 text-sm text-gray-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-800 dark:text-slate-400 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                    >{{ $landing['label'] }}</a>
                @endforeach
            </div>
        </div>
    @endif

</main>

@endsection
