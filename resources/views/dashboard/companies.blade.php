@extends('layouts.app')

@section('content')
<x-dashboard-main title="My companies">
    <div class="rounded-2xl border border-gray-200 dark:border-slate-800 p-12 text-center">
        <p class="text-gray-400 dark:text-slate-500 text-sm">You have no saved companies yet.</p>
        <a href="{{ route('home') }}" class="mt-4 inline-block rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 transition">
            Explore companies
        </a>
    </div>
</x-dashboard-main>
@endsection
