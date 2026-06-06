<?php

namespace App\Http\Controllers;

use App\Models\Company;

class SearchController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function results()
    {
        $search = request('q');

        $companies = Company::query()
            ->with(['jobOffers.technologies'])
            ->whereHas('jobOffers')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('jobOffers.technologies', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            })
            ->withCount('jobOffers')
            ->orderByDesc('job_offers_count')
            ->paginate(10)
            ->withQueryString();

        return view('results', compact('companies', 'search'));
    }
}
