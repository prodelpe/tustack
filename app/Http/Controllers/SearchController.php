<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Province;
use App\Models\Technology;
use App\Support\LandingPages;
use App\Support\PublicSearch;
use App\Support\StructuredData;

class SearchController extends Controller
{
    public function map()
    {
        return view('map', PublicSearch::viewData());
    }

    public function home()
    {
        $savedFilters = auth()->check()
            ? auth()->user()->savedSearches()->get()->pluck('filters')->map(function (array $filters) {
                return [
                    'technologies' => $filters['technologies'] ?? [],
                    'provinces'    => $filters['provinces'] ?? [],
                    'query'        => $filters['query'] ?? '',
                ];
            })->all()
            : [];

        return view('home', array_merge(PublicSearch::viewData(), [
            'companiesCount'    => Company::query()->inCatalogue()->count(),
            'technologiesCount' => Technology::query()->count(),
            'provincesCount'    => Province::query()->count(),
            'savedFilters'      => $savedFilters,
            'highlights'        => LandingPages::highlights(),
            'schema'            => StructuredData::website(),
        ]));
    }
}
