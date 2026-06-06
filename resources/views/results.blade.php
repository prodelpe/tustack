@extends('layouts.app')

@section('content')

<header class="sticky top-0 z-10 border-b border-gray-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-950/80">
    <div class="mx-auto flex max-w-5xl items-center gap-4 px-4 py-4">
        <a href="{{ route('home') }}" class="shrink-0 text-xl font-bold tracking-tight text-gray-900 dark:text-white">
            Find Your <span class="font-mono text-indigo-500 dark:text-indigo-400">DEV</span> Stack
        </a>
    </div>
</header>

<main class="mx-auto max-w-5xl px-4 py-8">
    <form id="filter-form" action="{{ route('search') }}" method="GET">

        <div class="mb-6 flex flex-wrap gap-3">

            {{-- Technologies multiselect --}}
            <div
                x-data="multiselect({
                    options: {{ Js::from($technologies->map(fn($t) => ['value' => $t->id, 'label' => $t->name])) }},
                    selected: {{ Js::from($techIds) }}
                })"
                class="flex flex-wrap items-center gap-1.5"
                @click.outside="open = false"
            >
                <template x-for="val in selected" :key="val">
                    <input type="hidden" name="technologies[]" :value="val">
                </template>

                <div class="relative">
                    <button type="button" @click="open = !open"
                        class="flex items-center gap-2 rounded-xl border px-4 py-2 text-sm transition"
                        :class="selected.length
                            ? 'border-indigo-400 bg-indigo-50 text-indigo-600 dark:border-indigo-500 dark:bg-indigo-500/10 dark:text-indigo-300'
                            : 'border-gray-300 bg-white text-gray-600 hover:border-gray-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600'"
                    >
                        <span>Technologies</span>
                        <svg class="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-transition
                        class="absolute top-full left-0 z-20 mt-2 w-64 rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div class="p-2 border-b border-gray-100 dark:border-slate-800">
                            <input type="text" x-model="search" placeholder="Search…"
                                class="w-full rounded-lg bg-gray-100 px-3 py-1.5 text-sm text-gray-900 placeholder-gray-400 outline-none dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                        </div>
                        <div class="max-h-56 overflow-y-auto p-1">
                            <template x-for="option in filtered" :key="option.value">
                                <button type="button" @click="toggle(option.value)"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-sm transition"
                                    :class="isSelected(option.value)
                                        ? 'text-indigo-600 dark:text-indigo-400'
                                        : 'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                                >
                                    <span x-text="option.label"></span>
                                    <svg x-show="isSelected(option.value)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <template x-for="val in selected" :key="val">
                    <span class="flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs text-indigo-600 dark:border-indigo-500/30 dark:bg-indigo-500/15 dark:text-indigo-300">
                        <span x-text="labelFor(val)"></span>
                        <button type="button" @click="remove(val)" class="ml-0.5 opacity-50 hover:opacity-100 leading-none">×</button>
                    </span>
                </template>
            </div>

            {{-- Provinces multiselect --}}
            <div
                x-data="multiselect({
                    options: {{ Js::from($provinces->map(fn($p) => ['value' => $p->id, 'label' => $p->name])) }},
                    selected: {{ Js::from($provinceIds) }}
                })"
                class="flex flex-wrap items-center gap-1.5"
                @click.outside="open = false"
            >
                <template x-for="val in selected" :key="val">
                    <input type="hidden" name="provinces[]" :value="val">
                </template>

                <div class="relative">
                    <button type="button" @click="open = !open"
                        class="flex items-center gap-2 rounded-xl border px-4 py-2 text-sm transition"
                        :class="selected.length
                            ? 'border-violet-400 bg-violet-50 text-violet-600 dark:border-violet-500 dark:bg-violet-500/10 dark:text-violet-300'
                            : 'border-gray-300 bg-white text-gray-600 hover:border-gray-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-600'"
                    >
                        <span>Province</span>
                        <svg class="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <div x-show="open" x-transition
                        class="absolute top-full left-0 z-20 mt-2 w-56 rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div class="p-2 border-b border-gray-100 dark:border-slate-800">
                            <input type="text" x-model="search" placeholder="Search…"
                                class="w-full rounded-lg bg-gray-100 px-3 py-1.5 text-sm text-gray-900 placeholder-gray-400 outline-none dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                        </div>
                        <div class="max-h-56 overflow-y-auto p-1">
                            <template x-for="option in filtered" :key="option.value">
                                <button type="button" @click="toggle(option.value)"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-1.5 text-sm transition"
                                    :class="isSelected(option.value)
                                        ? 'text-violet-600 dark:text-violet-400'
                                        : 'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                                >
                                    <span x-text="option.label"></span>
                                    <svg x-show="isSelected(option.value)" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <template x-for="val in selected" :key="val">
                    <span class="flex items-center gap-1 rounded-full border border-violet-200 bg-violet-50 px-2.5 py-1 text-xs text-violet-600 dark:border-violet-500/30 dark:bg-violet-500/15 dark:text-violet-300">
                        <span x-text="labelFor(val)"></span>
                        <button type="button" @click="remove(val)" class="ml-0.5 opacity-50 hover:opacity-100 leading-none">×</button>
                    </span>
                </template>
            </div>

            <button type="submit"
                class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-500">
                Apply filters
            </button>

            @if($techIds || $provinceIds)
                <a href="{{ route('search') }}"
                    class="rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-500 transition hover:border-gray-400 hover:text-gray-700 dark:border-slate-700 dark:text-slate-400 dark:hover:border-slate-600 dark:hover:text-white">
                    Clear filters
                </a>
            @endif

        </div>
    </form>

    <p class="mb-6 text-sm text-gray-500 dark:text-slate-400">
        Showing <span class="font-medium text-gray-900 dark:text-white">{{ $companies->total() }}</span> companies
    </p>

    @forelse ($companies as $company)
        @php
            $companyTechs = $company->jobOffers->flatMap->technologies->unique('id')->sortBy('name');
        @endphp
        <a href="{{ route('companies.show', $company) }}"
            class="mb-4 block rounded-2xl border border-gray-200 bg-white p-6 transition hover:border-gray-300 hover:shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
        >
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $company->name }}</h2>
                    @if ($company->city || $company->province)
                        <p class="mt-0.5 text-sm text-gray-500 dark:text-slate-400">
                            {{ collect([$company->city, $company->province?->name])->filter()->unique()->implode(', ') }}
                        </p>
                    @endif
                </div>
                <span class="shrink-0 rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-500 dark:bg-slate-800 dark:text-slate-400">
                    {{ $company->job_offers_count }} {{ Str::plural('position', $company->job_offers_count) }}
                </span>
            </div>

            @if ($companyTechs->isNotEmpty())
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($companyTechs as $tech)
                        <span class="rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1 text-xs font-medium text-indigo-600 dark:border-indigo-500/20 dark:bg-indigo-500/10 dark:text-indigo-300">
                            {{ $tech->name }}
                        </span>
                    @endforeach
                </div>
            @endif
        </a>
    @empty
        <div class="rounded-2xl border border-gray-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900">
            <p class="text-gray-500 dark:text-slate-400">No companies found for the selected filters.</p>
            <a href="{{ route('search') }}" class="mt-4 inline-block text-sm text-indigo-500 hover:text-indigo-600 dark:text-indigo-400 dark:hover:text-indigo-300">Clear filters</a>
        </div>
    @endforelse

    @if ($companies->hasPages())
        <div class="mt-8">
            {{ $companies->links() }}
        </div>
    @endif

</main>
@endsection
