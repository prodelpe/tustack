@extends('layouts.app')

@section('content')
<main class="flex min-h-screen flex-col items-center justify-center px-4">

    <div class="mb-4 inline-flex items-center gap-2 rounded-full border border-indigo-500/30 bg-indigo-500/10 px-4 py-1.5 text-sm text-indigo-300">
        <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
        Discover companies by their tech stack
    </div>

    <h1 class="mb-4 text-center text-6xl font-bold tracking-tight text-white md:text-7xl">
        Find Your <span class="bg-gradient-to-r from-indigo-400 to-violet-400 bg-clip-text text-transparent">Stack</span>
    </h1>

    <p class="mb-10 max-w-lg text-center text-lg text-slate-400">
        Search by technology or company name and find where your stack is actually used.
    </p>

    <form action="{{ route('search') }}" method="GET" class="w-full max-w-xl">
        <div class="flex overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl shadow-indigo-500/10 focus-within:border-indigo-500 focus-within:shadow-indigo-500/20 transition-all">
            <input
                type="text"
                name="q"
                placeholder="Laravel, Vue, React, Symfony…"
                autofocus
                class="flex-1 bg-transparent px-5 py-4 text-white placeholder-slate-500 outline-none text-base"
            >
            <button type="submit" class="m-1.5 rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 active:scale-95">
                Search
            </button>
        </div>
    </form>

</main>
@endsection
