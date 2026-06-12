<?php

namespace App\Http\Controllers;

class SearchController extends Controller
{
    public function home()
    {
        return view('home', [
            'meilisearchHost'    => config('scout.meilisearch.host'),
            'meilisearchKey'     => config('scout.meilisearch.key'),
            'technologiesCount'  => \App\Models\Technology::count(),
            'provincesCount'     => \App\Models\Province::count(),
        ]);
    }
}
