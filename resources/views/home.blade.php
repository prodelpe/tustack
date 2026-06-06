@extends('layouts.app')

@section('content')
<main class="flex min-h-screen flex-col items-center justify-center px-4">

    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm text-indigo-600 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
        <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
        Discover companies by their tech stack
    </div>

    <h1 class="mb-4 text-center text-6xl font-bold tracking-tight text-gray-900 md:text-7xl dark:text-white">
        Find Your <span class="bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent dark:from-indigo-400 dark:to-violet-400">Stack</span>
    </h1>

    <p class="mb-10 max-w-md text-center text-lg text-gray-500 dark:text-slate-400">
        Discover which companies use the technologies you love.
    </p>

    <form
        action="{{ route('search') }}"
        method="GET"
        x-data="homeFilters({
            techOptions: {{ Js::from($technologies->map(fn($t) => ['value' => $t->id, 'label' => $t->name])) }},
            provinceOptions: {{ Js::from($provinces->map(fn($p) => ['value' => $p->id, 'label' => $p->name])) }}
        })"
        @click.outside="techOpen = false; provinceOpen = false"
        class="flex w-full max-w-4xl flex-col items-center gap-5"
    >
        <template x-for="val in techSelected" :key="'t' + val">
            <input type="hidden" name="technologies[]" :value="val">
        </template>
        <template x-for="val in provinceSelected" :key="'p' + val">
            <input type="hidden" name="provinces[]" :value="val">
        </template>

        <div class="flex flex-wrap items-center justify-center gap-2">

            {{-- What? --}}
            <div class="relative">
                <button
                    type="button"
                    @click="techOpen = !techOpen; provinceOpen = false"
                    class="flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition"
                    :class="techOpen
                        ? 'border-indigo-400 bg-indigo-50 text-indigo-600 dark:border-indigo-500 dark:bg-indigo-500/10 dark:text-indigo-300'
                        : 'border-gray-300 bg-white text-gray-600 hover:border-gray-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500'"
                >
                    <span class="font-semibold text-gray-400 dark:text-slate-500">What?</span>
                    <span x-show="!techSelected.length" class="text-gray-400 dark:text-slate-400">Any stack</span>
                    <span x-show="techSelected.length" x-text="techSelected.length + ' selected'" class="text-indigo-600 dark:text-indigo-300"></span>
                    <svg class="w-3.5 h-3.5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div
                    x-show="techOpen"
                    x-transition
                    class="absolute top-full left-0 z-20 mt-2 w-72 rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                >
                    <div class="p-2 border-b border-gray-100 dark:border-slate-800">
                        <input type="text" x-model="techSearch" placeholder="Search technology…"
                            class="w-full rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 outline-none dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                    </div>
                    <div class="max-h-60 overflow-y-auto p-1">
                        <template x-for="option in filteredTechs" :key="option.value">
                            <button type="button" @click="toggleTech(option.value)"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition"
                                :class="isTechSelected(option.value)
                                    ? 'text-indigo-600 dark:text-indigo-400'
                                    : 'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                            >
                                <span x-text="option.label"></span>
                                <svg x-show="isTechSelected(option.value)" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Tech chips --}}
            <template x-for="val in techSelected" :key="'tc' + val">
                <span class="flex items-center gap-1 rounded-full border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm text-indigo-600 dark:border-indigo-500/30 dark:bg-indigo-500/15 dark:text-indigo-300">
                    <span x-text="labelFor(techOptions, val)"></span>
                    <button type="button" @click="removeTech(val)" class="ml-1 opacity-50 hover:opacity-100 leading-none">×</button>
                </span>
            </template>

            {{-- Where? --}}
            <div class="relative">
                <button
                    type="button"
                    @click="provinceOpen = !provinceOpen; techOpen = false"
                    class="flex items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium transition"
                    :class="provinceOpen
                        ? 'border-violet-400 bg-violet-50 text-violet-600 dark:border-violet-500 dark:bg-violet-500/10 dark:text-violet-300'
                        : 'border-gray-300 bg-white text-gray-600 hover:border-gray-400 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-slate-500'"
                >
                    <span class="font-semibold text-gray-400 dark:text-slate-500">Where?</span>
                    <span x-show="!provinceSelected.length" class="text-gray-400 dark:text-slate-400">Anywhere</span>
                    <span x-show="provinceSelected.length" x-text="provinceSelected.length + ' selected'" class="text-violet-600 dark:text-violet-300"></span>
                    <svg class="w-3.5 h-3.5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>

                <div
                    x-show="provinceOpen"
                    x-transition
                    class="absolute top-full left-0 z-20 mt-2 w-64 rounded-xl border border-gray-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900"
                >
                    <div class="p-2 border-b border-gray-100 dark:border-slate-800">
                        <input type="text" x-model="provinceSearch" placeholder="Search province…"
                            class="w-full rounded-lg bg-gray-100 px-3 py-2 text-sm text-gray-900 placeholder-gray-400 outline-none dark:bg-slate-800 dark:text-white dark:placeholder-slate-500">
                    </div>
                    <div class="max-h-60 overflow-y-auto p-1">
                        <template x-for="option in filteredProvinces" :key="option.value">
                            <button type="button" @click="toggleProvince(option.value)"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-sm transition"
                                :class="isProvinceSelected(option.value)
                                    ? 'text-violet-600 dark:text-violet-400'
                                    : 'text-gray-700 hover:bg-gray-100 dark:text-slate-300 dark:hover:bg-slate-800'"
                            >
                                <span x-text="option.label"></span>
                                <svg x-show="isProvinceSelected(option.value)" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Province chips --}}
            <template x-for="val in provinceSelected" :key="'pc' + val">
                <span class="flex items-center gap-1 rounded-full border border-violet-200 bg-violet-50 px-3 py-1.5 text-sm text-violet-600 dark:border-violet-500/30 dark:bg-violet-500/15 dark:text-violet-300">
                    <span x-text="labelFor(provinceOptions, val)"></span>
                    <button type="button" @click="removeProvince(val)" class="ml-1 opacity-50 hover:opacity-100 leading-none">×</button>
                </span>
            </template>

        </div>

        <button type="submit"
            class="rounded-full bg-indigo-600 px-10 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-500 active:scale-95">
            Discover companies
        </button>

    </form>

</main>
@endsection
