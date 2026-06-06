<?php

namespace App\Http\Controllers;

use App\Models\Company;

class CompanyController extends Controller
{
    public function show(Company $company)
    {
        $company->load(['jobOffers.technologies']);

        $technologies = $company->jobOffers
            ->flatMap->technologies
            ->unique('id')
            ->sortBy('name')
            ->values();

        $jobOffers = $company->jobOffers()
            ->orderByDesc('published_at')
            ->get();

        return view('company', compact('company', 'technologies', 'jobOffers'));
    }
}
