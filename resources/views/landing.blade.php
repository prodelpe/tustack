@extends('layouts.app')

@php
    $location = $province?->name ?? __('landing.country');
    $replacements = [
        'technology' => $technology->name,
        'location'   => $location,
        'count'      => $stats['companies'],
        'offers'     => $stats['offers'],
    ];
@endphp

@section('title', __('landing.meta_title', $replacements))
@section('description', __('landing.meta_description', $replacements))

@section('content')
<main class="mx-auto max-w-6xl px-4 py-10">

    <header class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
            {{ __('landing.heading', $replacements) }}
        </h1>

        <p class="mt-3 max-w-3xl text-gray-600 dark:text-slate-400">
            {{ __('landing.intro', $replacements) }}
            @if($stats['avg_salary'])
                {{ __('landing.intro_salary', ['salary' => number_format($stats['avg_salary'], 0, ',', '.')]) }}
            @endif
        </p>

        <x-outline-button
            href="{{ route('home', array_filter(['tech[0]' => $technology->name, 'prov[0]' => $province?->name])) }}"
            class="mt-5 gap-1.5 px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-300"
        >
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            {{ __('landing.search_cta') }}
        </x-outline-button>
    </header>

    <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('landing.companies_heading') }}</h2>

    <ul class="divide-y divide-gray-100 rounded-2xl border border-gray-200 dark:divide-slate-800 dark:border-slate-800">
        @foreach($companies as $company)
            <li class="flex items-center justify-between gap-4 px-5 py-4">
                <div class="min-w-0">
                    <a href="{{ route('companies.show', $company) }}" class="font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                        {{ $company->name }}
                    </a>
                    @if($company->city || $company->province)
                        <p class="mt-0.5 truncate text-sm text-gray-500 dark:text-slate-400">
                            {{ collect([$company->city, $company->province?->name])->filter()->unique()->join(', ') }}
                        </p>
                    @endif
                </div>
                <span class="shrink-0 text-sm text-gray-400 dark:text-slate-500">
                    {{ $company->job_offers_count }} {{ __('landing.offers_label') }}
                </span>
            </li>
        @endforeach
    </ul>

    @if($stats['companies'] > $companies->count())
        <p class="mt-3 text-xs text-gray-400 dark:text-slate-500">
            {{ __('landing.truncated', ['shown' => $companies->count(), 'total' => $stats['companies']]) }}
        </p>
    @endif

    @if($otherProvinces->isNotEmpty())
        <section class="mt-12">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                {{ __('landing.other_provinces', ['technology' => $technology->name]) }}
            </h2>
            <div class="flex flex-wrap gap-2">
                @foreach($otherProvinces as $other)
                    <a
                        href="{{ route('landing.technology-province', ['technology' => $technology, 'province' => $other]) }}"
                        class="rounded-full border border-gray-200 px-3 py-1 text-sm text-gray-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-800 dark:text-slate-400 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                    >{{ $other->name }} <span class="text-gray-400 dark:text-slate-500">{{ $other->companies_count }}</span></a>
                @endforeach
            </div>
        </section>
    @endif

    @if($otherTechnologies->isNotEmpty())
        <section class="mt-10">
            <h2 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">
                {{ __('landing.other_technologies', ['location' => $location]) }}
            </h2>
            <div class="flex flex-wrap gap-2">
                @foreach($otherTechnologies as $other)
                    <a
                        href="{{ route('landing.technology-province', ['technology' => $other, 'province' => $province]) }}"
                        class="rounded-full border border-gray-200 px-3 py-1 text-sm text-gray-600 transition hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-800 dark:text-slate-400 dark:hover:border-indigo-500/40 dark:hover:text-indigo-400"
                    >{{ $other->name }} <span class="text-gray-400 dark:text-slate-500">{{ $other->companies_count }}</span></a>
                @endforeach
            </div>
        </section>
    @endif

</main>
@endsection
