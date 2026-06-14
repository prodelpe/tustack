@extends('layouts.app')

@section('content')
<main class="mx-auto max-w-lg px-4 py-20 text-center">
    <p class="text-4xl mb-4">👋</p>
    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">You've been unsubscribed</h1>
    <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">
        You won't receive any more search alerts. You can re-enable them from your profile at any time.
    </p>
    <a href="{{ route('home') }}" class="mt-6 inline-block text-sm text-indigo-600 hover:underline dark:text-indigo-400">
        Back to search
    </a>
</main>
@endsection
