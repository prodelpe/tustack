@extends('layouts.app')

@section('content')
<main class="mx-auto max-w-6xl px-4 py-10">

    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-8">My alerts</h1>

    <div class="rounded-2xl border border-gray-200 dark:border-slate-800 p-12 text-center">
        <p class="text-gray-400 dark:text-slate-500 text-sm">You have no alerts yet.</p>
        <a href="{{ route('home') }}" class="mt-4 inline-block rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 transition">
            Explore companies
        </a>
    </div>

</main>
@endsection
