<?php

namespace App\Http\Controllers;


class SearchController extends Controller
{
    public function home()
    {
        $technologies = \App\Models\Technology::orderBy('name')->get(['id', 'name']);
        $provinces = \App\Models\Province::orderBy('name')->get(['id', 'name']);

        return view('home', compact('technologies', 'provinces'));
    }

    public function results()
    {
        return view('results', [
            'meilisearchHost' => config('scout.meilisearch.host'),
            'meilisearchKey'  => config('scout.meilisearch.key'),
        ]);
    }
}
