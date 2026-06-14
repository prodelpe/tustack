@extends('layouts.app')

@section('content')
<x-dashboard-main title="My companies">
    @forelse ($companies as $company)
        <a href="{{ route('companies.show', $company) }}"
            class="group mb-3 flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-4 transition hover:border-gray-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
        >
            <div>
                <p class="font-medium text-gray-900 group-hover:text-indigo-600 dark:text-white dark:group-hover:text-indigo-300">
                    {{ $company->name }}
                </p>
                @if ($company->city || $company->province)
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">
                        {{ collect([$company->city, $company->province?->name])->filter()->unique()->implode(', ') }}
                    </p>
                @endif
                @if ($company->jobOffers->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach ($company->jobOffers->flatMap->technologies->unique('id')->sortBy('name')->take(5) as $tech)
                            <x-tech-badge class="text-xs px-2 py-0.5">{{ $tech->name }}</x-tech-badge>
                        @endforeach
                    </div>
                @endif
            </div>
            <svg class="w-4 h-4 shrink-0 text-gray-300 group-hover:text-indigo-500 dark:text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @empty
        <div class="rounded-2xl border border-gray-200 dark:border-slate-800 p-12 text-center">
            <p class="text-gray-400 dark:text-slate-500 text-sm">You have no saved companies yet.</p>
            <a href="{{ route('home') }}" class="mt-4 inline-block rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 transition">
                Explore companies
            </a>
        </div>
    @endforelse
</x-dashboard-main>
@endsection
