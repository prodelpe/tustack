<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        $salary = $this->getSalaryStats($company);

        $similarCompanies = $company->similarCompanies();

        return view('company', compact('company', 'technologies', 'jobOffers', 'isSaved', 'salary', 'similarCompanies'));
    }

    private function getSalaryStats(Company $company): ?array
    {
        $offers = $company->jobOffers()->where('salary_min', '>=', 10000);
        $count = $offers->count();

        if ($count === 0) {
            return null;
        }

        return [
            'range_min'    => $offers->min('salary_min'),
            'range_max'    => $offers->max('salary_max'),
            'avg_salary'   => $offers->avg(DB::raw('(salary_min + salary_max) / 2')),
            'offers_count' => $count,
        ];
    }
}
