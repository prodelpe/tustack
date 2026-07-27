<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Province;
use App\Models\Technology;
use App\Support\SearchUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LandingController extends Controller
{
    public function technology(Technology $technology): View
    {
        return $this->page($technology, null);
    }

    public function technologyInProvince(Technology $technology, Province $province): View
    {
        return $this->page($technology, $province);
    }

    private function page(Technology $technology, ?Province $province): View
    {
        $data = Cache::remember(
            $this->cacheKey($technology, $province),
            now()->addHours(12),
            fn () => $this->build($technology, $province)
        );

        abort_if($data['stats']['companies'] < config('seo.minimum_companies'), 404);

        return view('landing', $data + [
            'technology' => $technology,
            'province'   => $province,
            'searchUrl'  => SearchUrl::withFilters([$technology->name], array_filter([$province?->name])),
        ]);
    }

    private function build(Technology $technology, ?Province $province): array
    {
        return [
            'companies'      => $this->companies($technology, $province),
            'stats'          => $this->stats($technology, $province),
            'otherProvinces' => $this->provincesWithTechnology($technology, $province),
            'otherTechnologies' => $province
                ? $this->technologiesInProvince($province, $technology)
                : new Collection(),
        ];
    }

    private function companies(Technology $technology, ?Province $province): Collection
    {
        return $this->scope($technology, $province)
            ->with('province')
            ->withCount('jobOffers')
            ->orderByDesc('job_offers_count')
            ->orderBy('name')
            ->limit(config('seo.companies_per_page'))
            ->get();
    }

    private function stats(Technology $technology, ?Province $province): array
    {
        $offers = DB::table('job_offers')
            ->join('job_offer_technology', 'job_offer_technology.job_offer_id', '=', 'job_offers.id')
            ->join('companies', 'companies.id', '=', 'job_offers.company_id')
            ->where('job_offer_technology.technology_id', $technology->id)
            ->when($province, fn ($query) => $query->where('companies.province_id', $province->id));

        return [
            'companies'  => $this->scope($technology, $province)->count(),
            'offers'     => (clone $offers)->count(),
            'avg_salary' => (clone $offers)
                ->where('job_offers.salary_min', '>=', 10000)
                ->avg(DB::raw('(job_offers.salary_min + job_offers.salary_max) / 2')),
        ];
    }

    /**
     * Provinces where this technology has enough companies to deserve a page,
     * which is exactly the set we can link to without creating dead ends.
     */
    private function provincesWithTechnology(Technology $technology, ?Province $exclude): Collection
    {
        return Province::query()
            ->select('provinces.*')
            ->selectRaw('COUNT(DISTINCT companies.id) as companies_count')
            ->join('companies', 'companies.province_id', '=', 'provinces.id')
            ->join('job_offers', 'job_offers.company_id', '=', 'companies.id')
            ->join('job_offer_technology', 'job_offer_technology.job_offer_id', '=', 'job_offers.id')
            ->where('job_offer_technology.technology_id', $technology->id)
            ->when($exclude, fn (Builder $query) => $query->whereKeyNot($exclude->id))
            ->groupBy('provinces.id')
            ->havingRaw('COUNT(DISTINCT companies.id) >= ?', [config('seo.minimum_companies')])
            ->orderByDesc('companies_count')
            ->limit(12)
            ->get();
    }

    private function technologiesInProvince(Province $province, Technology $exclude): Collection
    {
        return Technology::query()
            ->select('technologies.*')
            ->selectRaw('COUNT(DISTINCT companies.id) as companies_count')
            ->join('job_offer_technology', 'job_offer_technology.technology_id', '=', 'technologies.id')
            ->join('job_offers', 'job_offers.id', '=', 'job_offer_technology.job_offer_id')
            ->join('companies', 'companies.id', '=', 'job_offers.company_id')
            ->where('companies.province_id', $province->id)
            ->whereKeyNot($exclude->id)
            ->groupBy('technologies.id')
            ->havingRaw('COUNT(DISTINCT companies.id) >= ?', [config('seo.minimum_companies')])
            ->orderByDesc('companies_count')
            ->limit(12)
            ->get();
    }

    private function scope(Technology $technology, ?Province $province): Builder
    {
        return Company::query()
            ->whereHas('jobOffers.technologies', fn (Builder $query) => $query->whereKey($technology->id))
            ->when($province, fn (Builder $query) => $query->where('province_id', $province->id));
    }

    private function cacheKey(Technology $technology, ?Province $province): string
    {
        return implode(':', array_filter([
            'landing',
            app()->getLocale(),
            $technology->slug,
            $province?->slug,
        ]));
    }
}
