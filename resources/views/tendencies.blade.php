@extends('layouts.app')

@section('title', __('seo.tendencies_title'))
@section('description', __('seo.tendencies_description'))

@section('content')

<main class="mx-auto max-w-6xl px-4 py-8">

    @if(!$meilisearchAvailable)
        <div class="flex min-h-[60vh] flex-col items-center justify-center gap-6 text-center">
            <div class="rounded-2xl border border-red-200 bg-red-50 p-15 dark:border-red-900/40 dark:bg-red-950/30">
                <p class="text-lg font-semibold text-gray-800 dark:text-white">{{ __('tendencies.unavailable') }}</p>
                <p class="mt-2 text-sm text-gray-500 dark:text-slate-400">{{ __('home.search_unavailable_detail') }}</p>
            </div>
        </div>
    @else
        <div class="flex gap-8">

            <x-search-sidebar :showConsultancyFilter="false" :showProvinces="false" />

            <div class="flex-1 min-w-0">
                <div class="mb-4 flex items-center gap-4">
                    <x-back-to-list-link />
                    <div id="clear-filters"></div>
                </div>

                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ __('tendencies.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ __('tendencies.subtitle') }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <div style="position: relative; height: 460px;">
                        <canvas id="trends-chart"></canvas>
                    </div>
                </div>
            </div>

        </div>
    @endif

</main>

@push('scripts')
<script>
    window.__MEILISEARCH_HOST__    = @json($meilisearchHost);
    window.__MEILISEARCH_KEY__     = @json($meilisearchKey);
    window.__TENDENCY_DATA_URL__   = @json(route('tendency-data'));
    window.__HOME_URL__            = @json(route('home'));
</script>
@vite('resources/js/trends.js')
@endpush

@endsection
