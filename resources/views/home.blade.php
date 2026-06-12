@extends('layouts.app')

@section('content')
<main class="flex min-h-screen flex-col items-center justify-center px-4 py-16">

    <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-indigo-200 bg-indigo-50 px-4 py-1.5 text-sm text-indigo-600 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300">
        <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
        Discover companies by their tech stack
    </div>

    <h1 class="mb-4 text-center text-6xl font-bold tracking-tight text-gray-900 md:text-7xl dark:text-white">
        Find Your <span class="font-mono bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent dark:from-indigo-400 dark:to-violet-400">DEV</span> Stack
    </h1>

    <p class="mb-12 max-w-md text-center text-lg text-gray-500 dark:text-slate-400">
        Discover which companies use the technologies you love.
    </p>

    <form id="home-form" class="w-full max-w-2xl space-y-4">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">
                    What?
                </label>
                <select id="select-technologies" name="technologies[]" multiple placeholder="Any stack">
                    @foreach ($technologies as $tech)
                        <option value="{{ $tech->name }}">{{ $tech->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-1.5 block text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-slate-500">
                    Where?
                </label>
                <select id="select-provinces" name="provinces[]" multiple placeholder="Anywhere">
                    @foreach ($provinces as $province)
                        <option value="{{ $province->name }}">{{ $province->name }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        <div class="flex justify-center pt-2">
            <button type="submit"
                class="rounded-full bg-indigo-600 px-12 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-500 active:scale-95">
                Discover companies
            </button>
        </div>

    </form>

</main>

@push('scripts')
@vite('resources/js/home.js')
@endpush

@endsection
