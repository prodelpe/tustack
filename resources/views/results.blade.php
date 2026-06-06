@extends('layouts.app')

@section('content')

{{-- Header --}}
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
                    value="{{ $search }}"
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

{{-- Results --}}
<main class="mx-auto max-w-5xl px-4 py-10">

    <p class="mb-6 text-sm text-slate-400">
        @if ($search)
            <span class="text-white font-medium">{{ $companies->total() }}</span> companies found for
            <span class="text-indigo-400 font-medium">{{ $search }}</span>
        @else
            Showing all <span class="text-white font-medium">{{ $companies->total() }}</span> companies
        @endif
    </p>

    @forelse ($companies as $company)
        @php
            $technologies = $company->jobOffers->flatMap->technologies->unique('id')->sortBy('name');
        @endphp
        <div class="mb-4 rounded-2xl border border-slate-800 bg-slate-900 p-6 transition hover:border-slate-700">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-white">{{ $company->name }}</h2>
                    @if ($company->province || $company->location)
                        <p class="mt-0.5 text-sm text-slate-400">
                            {{ $company->province ?? $company->location }}
                        </p>
                    @endif
                </div>
                <span class="shrink-0 rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-400">
                    {{ $company->job_offers_count }} {{ Str::plural('position', $company->job_offers_count) }}
                </span>
            </div>

            @if ($technologies->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($technologies as $tech)
                        <span class="rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1 text-xs font-medium text-indigo-300">
                            {{ $tech->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-12 text-center">
            <p class="text-slate-400">No companies found{{ $search ? ' for "' . $search . '"' : '' }}.</p>
        </div>
    @endforelse

    {{-- Pagination --}}
    @if ($companies->hasPages())
        <div class="mt-8">
            {{ $companies->links() }}
        </div>
    @endif

</main>
@endsection
