@extends('layouts.app')

@section('content')

{{-- Header --}}
<header class="border-b border-slate-800 bg-slate-950/80 backdrop-blur sticky top-0 z-10">
    <div class="mx-auto flex max-w-5xl items-center gap-6 px-4 py-4">
        <a href="{{ route('home') }}" class="shrink-0 text-xl font-bold tracking-tight text-white">
            Find Your <span class="text-indigo-400">Stack</span>
        </a>
        <form id="search-form" action="{{ route('search') }}" method="GET" class="flex-1">
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

{{-- Filters + Results --}}
<main class="mx-auto max-w-5xl px-4 py-8">
    <form id="filter-form" action="{{ route('search') }}" method="GET">
        @if($search)
            <input type="hidden" name="q" value="{{ $search }}">
        @endif

        <div class="mb-6 flex flex-wrap gap-3">

            {{-- Technologies multiselect --}}
            <div
                x-data="multiselect({
                    options: {{ Js::from($technologies->map(fn($t) => ['value' => $t->id, 'label' => $t->name])) }},
                    selected: {{ Js::from(array_map('intval', $techIds)) }}
                })"
                class="relative"
                @click.outside="open = false"
            >
                {{-- Hidden inputs --}}
                <template x-for="val in selected" :key="val">
                    <input type="hidden" name="technologies[]" :value="val">
                </template>

                {{-- Trigger --}}
                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900 px-4 py-2 text-sm text-slate-300 transition hover:border-slate-600"
                    :class="selected.length ? 'border-indigo-500 text-white' : ''"
                >
                    <span x-text="selected.length ? selected.length + ' technologies' : 'Technologies'"></span>
                    <svg class="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                {{-- Dropdown --}}
                <div
                    x-show="open"
                    x-transition
                    class="absolute top-full left-0 z-20 mt-2 w-64 rounded-xl border border-slate-700 bg-slate-900 shadow-xl"
                >
                    <div class="p-2 border-b border-slate-800">
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Search…"
                            class="w-full bg-slate-800 rounded-lg px-3 py-1.5 text-sm text-white placeholder-slate-500 outline-none"
                        >
                    </div>
                    <div class="max-h-56 overflow-y-auto p-1">
                        <template x-for="option in filtered" :key="option.value">
                            <button
                                type="button"
                                @click="toggle(option.value)"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-sm transition hover:bg-slate-800"
                                :class="isSelected(option.value) ? 'text-indigo-400' : 'text-slate-300'"
                            >
                                <span x-text="option.label"></span>
                                <svg x-show="isSelected(option.value)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                    <div x-show="selected.length" class="p-2 border-t border-slate-800 flex flex-wrap gap-1">
                        <template x-for="val in selected" :key="val">
                            <span class="flex items-center gap-1 rounded-full bg-indigo-500/20 border border-indigo-500/30 px-2 py-0.5 text-xs text-indigo-300">
                                <span x-text="labelFor(val)"></span>
                                <button type="button" @click="remove(val)" class="hover:text-white">×</button>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Provinces multiselect --}}
            <div
                x-data="multiselect({
                    options: {{ Js::from($availableProvinces->map(fn($p) => ['value' => $p, 'label' => $p])) }},
                    selected: {{ Js::from($provinces) }}
                })"
                class="relative"
                @click.outside="open = false"
            >
                <template x-for="val in selected" :key="val">
                    <input type="hidden" name="provinces[]" :value="val">
                </template>

                <button
                    type="button"
                    @click="open = !open"
                    class="flex items-center gap-2 rounded-xl border border-slate-700 bg-slate-900 px-4 py-2 text-sm text-slate-300 transition hover:border-slate-600"
                    :class="selected.length ? 'border-indigo-500 text-white' : ''"
                >
                    <span x-text="selected.length ? selected.length + ' provinces' : 'Province'"></span>
                    <svg class="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div
                    x-show="open"
                    x-transition
                    class="absolute top-full left-0 z-20 mt-2 w-56 rounded-xl border border-slate-700 bg-slate-900 shadow-xl"
                >
                    <div class="p-2 border-b border-slate-800">
                        <input
                            type="text"
                            x-model="search"
                            placeholder="Search…"
                            class="w-full bg-slate-800 rounded-lg px-3 py-1.5 text-sm text-white placeholder-slate-500 outline-none"
                        >
                    </div>
                    <div class="max-h-56 overflow-y-auto p-1">
                        <template x-for="option in filtered" :key="option.value">
                            <button
                                type="button"
                                @click="toggle(option.value)"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-sm transition hover:bg-slate-800"
                                :class="isSelected(option.value) ? 'text-indigo-400' : 'text-slate-300'"
                            >
                                <span x-text="option.label"></span>
                                <svg x-show="isSelected(option.value)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                    <div x-show="selected.length" class="p-2 border-t border-slate-800 flex flex-wrap gap-1">
                        <template x-for="val in selected" :key="val">
                            <span class="flex items-center gap-1 rounded-full bg-indigo-500/20 border border-indigo-500/30 px-2 py-0.5 text-xs text-indigo-300">
                                <span x-text="labelFor(val)"></span>
                                <button type="button" @click="remove(val)" class="hover:text-white">×</button>
                            </span>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Apply button --}}
            <button
                type="submit"
                class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500"
            >
                Apply filters
            </button>

            @if($techIds || $provinces)
                <a href="{{ route('search', ['q' => $search]) }}" class="rounded-xl border border-slate-700 px-4 py-2 text-sm text-slate-400 transition hover:border-slate-600 hover:text-white">
                    Clear filters
                </a>
            @endif

        </div>
    </form>

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
        <a href="{{ route('companies.show', $company) }}" class="mb-4 block rounded-2xl border border-slate-800 bg-slate-900 p-6 transition hover:border-slate-700">
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
        </a>
    @empty
        <div class="rounded-2xl border border-slate-800 bg-slate-900 p-12 text-center">
            <p class="text-slate-400">No companies found{{ $search ? ' for "' . $search . '"' : '' }}.</p>
        </div>
    @endforelse

    @if ($companies->hasPages())
        <div class="mt-8">
            {{ $companies->links() }}
        </div>
    @endif

</main>
@endsection
