<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

class SearchController extends Controller
{
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
            'meilisearchKey'       => config('scout.meilisearch.key'),
            'meilisearchAvailable' => $healthy,
            'technologiesCount'    => \App\Models\Technology::count(),
            'provincesCount'       => \App\Models\Province::count(),
            'savedFilters'         => $savedFilters,
        ]);
    }
}
