<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\LandingPages;
use App\Support\StructuredData;
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
        $landings       = $this->landings($company, $technologies);
        $breadcrumbs    = $this->breadcrumbs($company);
        $schema         = StructuredData::company($company, $technologies, $seoDescription, $breadcrumbs);

        return view('company', compact(
            'company',
            'technologies',
            'jobOffers',
            'isSaved',
            'salary',
            'similarCompanies',
            'seoTitle',
            'seoDescription',
            'landings',
            'breadcrumbs',
            'schema'
        ));
    }

    /**
     * A company usually works with several technologies, so naming one of them
     * in the trail would be picking arbitrarily and reading as if it were the
     * only one. The parent is the company search instead.
     *
     * @return array<int, array{label: string, url: string}>
     */
    private function breadcrumbs(Company $company): array
    {
        return [
            ['label' => 'TuStack', 'url' => route('home')],
            ['label' => __('nav.companies'), 'url' => route('home')],
            ['label' => $company->display_name, 'url' => url()->current()],
        ];
    }

    /**
     * Links to the landing pages this company belongs to, skipping the ones
     * that do not exist so no page ever links to a 404.
     *
     * @return array<int, array{label: string, url: string}>
     */
    private function landings(Company $company, Collection $technologies): array
    {
        $links = [];

        foreach ($technologies->take(4) as $technology) {
            if (LandingPages::hasCombination($technology->slug, $company->province?->slug)) {
                $links[] = [
                    'label' => __('landing.heading', [
                        'technology' => $technology->name,
                        'location'   => $company->province->name,
                    ]),
                    'url' => route('landing.technology-province', [
                        'technology' => $technology,
                        'province'   => $company->province,
                    ]),
                ];

                continue;
            }

            if (LandingPages::hasHub($technology->slug)) {
                $links[] = [
                    'label' => __('landing.heading', [
                        'technology' => $technology->name,
                        'location'   => __('landing.country'),
                    ]),
                    'url' => route('landing.technology', ['technology' => $technology]),
                ];
            }
        }

        return $links;
    }

    private function seoTitle(Company $company, Collection $technologies): string
    {
        $stack = $technologies->take(3)->pluck('name')->join(', ');

        if (blank($stack)) {
            return $company->display_name;
        }

        $location = $company->city ?: $company->province?->name;

        if (blank($location)) {
            return __('seo.company_title_no_location', [
                'company'      => $company->display_name,
                'technologies' => $stack,
            ]);
        }

        return __('seo.company_title', [
            'company'      => $company->display_name,
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
            return __('seo.company_description_minimal', ['company' => $company->display_name]);
        }

        return __('seo.company_description', [
            'company'      => $company->display_name,
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
