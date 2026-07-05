<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Models\Technology;
use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
    public function map()
    {
        $host = config('scout.meilisearch.host');

        try {
            $healthy = Http::timeout(2)->get("{$host}/health")->successful();
        } catch (\Throwable) {
            $healthy = false;
        }

        return view('map', [
            'meilisearchHost'      => $host,
            'meilisearchKey'       => env('MEILISEARCH_KEY', config('scout.meilisearch.key')),
            'meilisearchAvailable' => $healthy,
        ]);
    }

    public function home()
    {
        $host = config('scout.meilisearch.host');

        try {
            $healthy = Http::timeout(2)->get("{$host}/health")->successful();
        } catch (\Throwable) {
            $healthy = false;
        }

        $savedFilters = auth()->check()
            ? auth()->user()->savedSearches()->get()->pluck('filters')->map(fn ($f) => [
                'technologies' => $f['technologies'] ?? [],
                'provinces'    => $f['provinces'] ?? [],
                'query'        => $f['query'] ?? '',
            ])->all()
            : [];

        return view('home', [
            'meilisearchHost'      => $host,
            'meilisearchKey'       => env('MEILISEARCH_KEY', config('scout.meilisearch.key')),
            'meilisearchAvailable' => $healthy,
            'technologiesCount'    => Technology::query()->count(),
            'provincesCount'       => Province::query()->count(),
            'savedFilters'         => $savedFilters,
        ]);
    }
}
