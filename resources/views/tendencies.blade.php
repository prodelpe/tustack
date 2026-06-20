@extends('layouts.app')

@section('content')

<main class="mx-auto max-w-6xl px-4 py-10">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Tech trends</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Job offers per month · top 8 technologies · last 2 years</p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <div x-data="trendChart(@js($chartData))" class="relative h-96">
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

</main>

@endsection
