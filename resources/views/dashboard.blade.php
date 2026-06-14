@extends('layouts.app')

@section('content')
<x-dashboard-main title="My alerts">
    @if (!$alertsEnabled && $savedSearches->isNotEmpty())
        <div class="mb-6 flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-900/40 dark:bg-amber-950/30">
            <p class="text-sm text-amber-800 dark:text-amber-300">Email alerts are disabled. You won't receive notifications.</p>
            <form method="POST" action="{{ route('alerts.enable') }}">
                @csrf
                <button type="submit" class="text-sm font-medium text-amber-700 hover:text-amber-900 dark:text-amber-400 dark:hover:text-amber-200 underline">Re-enable</button>
            </form>
        </div>
    @endif
    @forelse ($savedSearches as $search)
        <div class="mb-3 flex items-center justify-between rounded-xl border border-gray-200 bg-white px-5 py-4 dark:border-slate-800 dark:bg-slate-900">
            <div>
                <p class="font-medium text-gray-900 dark:text-white">{{ $search->describe() }}</p>
                @if ($search->last_notified_at)
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">Last notified {{ $search->last_notified_at->diffForHumans() }}</p>
                @else
                    <p class="mt-0.5 text-xs text-gray-400 dark:text-slate-500">No notifications sent yet</p>
                @endif
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <a href="{{ url('/') . ($search->filters['technologies'] || $search->filters['provinces'] || $search->filters['query'] ? '?' . http_build_query(array_filter([
                    'technologies' => implode(',', $search->filters['technologies'] ?? []) ?: null,
                    'provinces'    => implode(',', $search->filters['provinces'] ?? []) ?: null,
                    'q'            => $search->filters['query'] ?: null,
                ])) : '') }}"
                   class="text-xs text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                    View results
                </a>
                <form method="POST" action="{{ route('saved-searches.destroy', $search) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition">Remove</button>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-gray-200 dark:border-slate-800 p-12 text-center">
            <p class="text-gray-400 dark:text-slate-500 text-sm">You have no alerts yet.</p>
            <a href="{{ route('home') }}" class="mt-4 inline-block rounded-lg bg-indigo-500 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-600 transition">
                Explore companies
            </a>
        </div>
    @endforelse
</x-dashboard-main>
@endsection
