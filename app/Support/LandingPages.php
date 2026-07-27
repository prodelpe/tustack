<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The set of landing pages that actually exist, shared by the sitemap and by
 * every internal link. Linking to a combination below the threshold would send
 * both crawlers and visitors to a 404.
 */
class LandingPages
{
    private const CACHE_KEY = 'landing.pages';

    /**
     * @return array{hubs: array<int, string>, combinations: array<string, array<int, string>>}
     */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(12), function () {
            return [
                'hubs'         => self::hubs(),
                'combinations' => self::combinations(),
            ];
        });
    }

    public static function hasHub(string $technologySlug): bool
    {
        return in_array($technologySlug, self::all()['hubs'], strict: true);
    }

    public static function hasCombination(string $technologySlug, ?string $provinceSlug): bool
    {
        if (blank($provinceSlug)) {
            return false;
        }

        return in_array($provinceSlug, self::all()['combinations'][$technologySlug] ?? [], strict: true);
    }

    /**
     * @return array<int, string> technology slugs with enough companies nationwide
     */
    private static function hubs(): array
    {
        return self::base()
            ->select('technologies.slug')
            ->groupBy('technologies.slug')
            ->havingRaw('COUNT(DISTINCT companies.id) >= ?', [config('seo.minimum_companies')])
            ->pluck('slug')
            ->all();
    }

    /**
     * @return array<string, array<int, string>> technology slug => province slugs
     */
    private static function combinations(): array
    {
        return self::base()
            ->select('technologies.slug as technology', 'provinces.slug as province')
            ->join('provinces', 'provinces.id', '=', 'companies.province_id')
            ->groupBy('technologies.slug', 'provinces.slug')
            ->havingRaw('COUNT(DISTINCT companies.id) >= ?', [config('seo.minimum_companies')])
            ->get()
            ->groupBy('technology')
            ->map(fn ($rows) => $rows->pluck('province')->all())
            ->all();
    }

    private static function base()
    {
        return DB::table('technologies')
            ->join('job_offer_technology', 'job_offer_technology.technology_id', '=', 'technologies.id')
            ->join('job_offers', 'job_offers.id', '=', 'job_offer_technology.job_offer_id')
            ->join('companies', 'companies.id', '=', 'job_offers.company_id');
    }
}
