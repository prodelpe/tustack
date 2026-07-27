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
        <div class="lg:flex lg:gap-8">

            <x-search-sidebar :showConsultancyFilter="false" :showProvinces="false" />

            <div class="min-w-0 flex-1">
                <x-view-switcher current="trends" />

                <div class="mb-4 flex flex-wrap items-center gap-3">
                    <div id="clear-filters"></div>
                </div>

                <div class="mb-6">
                    <h1 class="text-2xl font-bold sm:text-3xl text-gray-900 dark:text-white">{{ __('tendencies.title') }}</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">{{ __('tendencies.subtitle') }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="relative h-[320px] sm:h-[400px] lg:h-[460px]">
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
    window.__MAP_URL__             = @json(route('map'));
</script>
@vite('resources/js/trends.js')
@endpush

@endsection
