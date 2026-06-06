<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Technology;

class SearchController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function results()
    {
        $search = request('q');
        $techIds = array_filter((array) request('technologies', []));
        $provinces = array_filter((array) request('provinces', []));

        $companies = Company::query()
            ->with(['jobOffers.technologies'])
            ->whereHas('jobOffers')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('jobOffers.technologies', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->when($techIds, function ($query) use ($techIds) {
                foreach ($techIds as $id) {
                    $query->whereHas('jobOffers.technologies', fn ($q) => $q->where('technologies.id', $id));
                }
            })
            ->when($provinces, fn ($q) => $q->whereIn('province', $provinces))
            ->withCount('jobOffers')
            ->orderByDesc('job_offers_count')
            ->paginate(10)
            ->withQueryString();

        $technologies = Technology::orderBy('name')->get(['id', 'name']);
        $availableProvinces = Company::whereNotNull('province')->distinct()->orderBy('province')->pluck('province');

        return view('results', compact('companies', 'search', 'technologies', 'availableProvinces', 'techIds', 'provinces'));
    }
}
