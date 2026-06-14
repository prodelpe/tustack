@extends('layouts.app')

@section('content')
<main class="mx-auto max-w-3xl px-4 py-10">

    <a href="{{ route('companies.show', $company) }}"
        class="mb-6 inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 dark:text-slate-500 dark:hover:text-slate-300 transition">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $company->name }}
    </a>

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $jobOffer->title }}</h1>

        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-slate-400">
            @if ($company->city || $company->province)
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    {{ collect([$company->city, $company->province?->name])->filter()->unique()->implode(', ') }}
                </span>
            @endif

            @if ($jobOffer->published_at)
                <span>{{ $jobOffer->published_at->format('d M Y') }}</span>
            @endif

            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs dark:bg-slate-800">{{ $jobOffer->source }}</span>

            @if ($jobOffer->salary_min || $jobOffer->salary_max)
                <span class="font-medium text-green-600 dark:text-green-400">
                    {{ $jobOffer->salary_min ? $jobOffer->salary_min . 'k' : '' }}
                    {{ $jobOffer->salary_min && $jobOffer->salary_max ? '–' : '' }}
                    {{ $jobOffer->salary_max ? $jobOffer->salary_max . 'k' : '' }} €
                    @if ($jobOffer->salary_is_predicted)
                        <span class="text-xs font-normal text-gray-400 dark:text-slate-500">(estimated)</span>
                    @endif
                </span>
            @endif
        </div>

        @if ($jobOffer->technologies->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($jobOffer->technologies as $tech)
                    <x-tech-badge>{{ $tech->name }}</x-tech-badge>
                @endforeach
            </div>
        @endif
    </div>

    <div class="prose prose-sm max-w-none text-gray-700 dark:text-slate-300">
        {!! $jobOffer->description !!}
    </div>

    <div class="mt-10 border-t border-gray-200 dark:border-slate-800 pt-6">
        <a href="{{ $jobOffer->url }}" target="_blank" rel="noopener noreferrer"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-500 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-600 transition">
            Apply on {{ ucfirst($jobOffer->source) }}
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
        </a>
    </div>

</main>
@endsection
