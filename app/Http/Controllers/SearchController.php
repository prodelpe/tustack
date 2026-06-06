<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Province;
use App\Models\Technology;

class SearchController extends Controller
{
    public function home()
    {
        $technologies = Technology::orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name']);

        return view('home', compact('technologies', 'provinces'));
    }

    public function results()
    {
        $techIds = array_filter(array_map('intval', (array) request('technologies', [])));
        $provinceIds = array_filter(array_map('intval', (array) request('provinces', [])));

        $companies = Company::query()
            ->with(['jobOffers.technologies'])
            ->whereHas('jobOffers')
            ->when($techIds, function ($query) use ($techIds) {
                foreach ($techIds as $id) {
                    $query->whereHas('jobOffers.technologies', fn ($q) => $q->where('technologies.id', $id));
                }
            })
            ->when($provinceIds, fn ($q) => $q->whereIn('province_id', $provinceIds))
            ->withCount('jobOffers')
            ->orderByDesc('job_offers_count')
            ->paginate(10)
            ->withQueryString();

        $technologies = Technology::orderBy('name')->get(['id', 'name']);
        $provinces = Province::orderBy('name')->get(['id', 'name']);

        return view('results', compact('companies', 'technologies', 'provinces', 'techIds', 'provinceIds'));
    }
}
