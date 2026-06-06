<?php

namespace App\Http\Controllers;


class SearchController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function results()
    {
        return view('results', [
            'meilisearchHost' => config('scout.meilisearch.host'),
            'meilisearchKey'  => config('scout.meilisearch.key'),
        ]);
    }
}
