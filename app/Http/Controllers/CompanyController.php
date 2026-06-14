<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;

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
            ->paginate(10);

        $isSaved = Auth::check() && Auth::user()->savedCompanies()->where('company_id', $company->id)->exists();

        return view('company', compact('company', 'technologies', 'jobOffers', 'isSaved'));
    }
}
