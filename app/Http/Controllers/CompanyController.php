<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function show(Company $company)
    {
        $company->load(['jobOffers.technologies', 'province']);

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

        $seoTitle       = $this->seoTitle($company, $technologies);
        $seoDescription = $this->seoDescription($company, $technologies);

        return view('company', compact(
            'company',
            'technologies',
            'jobOffers',
            'isSaved',
            'salary',
            'similarCompanies',
            'seoTitle',
            'seoDescription'
        ));
    }

    private function seoTitle(Company $company, Collection $technologies): string
    {
        $stack = $technologies->take(3)->pluck('name')->join(', ');

        if (blank($stack)) {
            return $company->name;
        }

        $location = $company->city ?: $company->province?->name;

        if (blank($location)) {
            return __('seo.company_title_no_location', [
                'company'      => $company->name,
                'technologies' => $stack,
            ]);
        }

        return __('seo.company_title', [
            'company'      => $company->name,
            'technologies' => $stack,
            'location'     => $location,
        ]);
    }

    private function seoDescription(Company $company, Collection $technologies): string
    {
        $written = $company->description[app()->getLocale()] ?? null;

        if (filled($written)) {
            return Str::limit(strip_tags($written), 155);
        }

        $stack = $technologies->take(5)->pluck('name')->join(', ');

        if (blank($stack)) {
            return __('seo.company_description_minimal', ['company' => $company->name]);
        }

        return __('seo.company_description', [
            'company'      => $company->name,
            'technologies' => $stack,
        ]);
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
