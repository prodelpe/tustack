<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\LandingPages;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class SitemapController extends Controller
{
    private const CACHE_KEY = 'sitemap.xml';

    public function __invoke(): Response
    {
        // The xml declaration lives here because Blade cannot emit `<?xml`
        // without the compiler mistaking it for a php tag.
        $xml = Cache::remember(self::CACHE_KEY, now()->addDay(), function () {
            return '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL . view('sitemap', [
                'entries' => $this->entries(),
            ])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * Every entry carries the same page in all locales, which is what tells
     * search engines these are translations and not duplicated content.
     */
    private function entries(): array
    {
        $locales = array_keys(LaravelLocalization::getSupportedLocales());

        $entries = [];

        $entries[] = [
            'urls'       => $this->homeUrls($locales),
            'lastmod'    => null,
            'changefreq' => 'daily',
            'priority'   => '1.0',
        ];

        foreach (['map', 'tendencies'] as $route) {
            $entries[] = [
                'urls'       => $this->localizedUrls($locales, 'routes.' . $route),
                'lastmod'    => null,
                'changefreq' => 'daily',
                'priority'   => '0.7',
            ];
        }

        $landings = LandingPages::all();

        foreach ($landings['hubs'] as $technology) {
            $entries[] = [
                'urls'       => $this->localizedUrls($locales, 'routes.technology', ['technology' => $technology]),
                'lastmod'    => null,
                'changefreq' => 'weekly',
                'priority'   => '0.8',
            ];
        }

        foreach ($landings['combinations'] as $technology => $provinces) {
            foreach ($provinces as $province) {
                $entries[] = [
                    'urls'       => $this->localizedUrls($locales, 'routes.technology_province', [
                        'technology' => $technology,
                        'province'   => $province,
                    ]),
                    'lastmod'    => null,
                    'changefreq' => 'weekly',
                    'priority'   => '0.8',
                ];
            }
        }

        // A company with no stack answers no search on this site, so listing it
        // asks Google to index a page that says nothing.
        Company::query()
            ->inCatalogue()
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->chunk(500, function ($companies) use ($locales, &$entries) {
                foreach ($companies as $company) {
                    $entries[] = [
                        'urls'       => $this->localizedUrls($locales, 'routes.companies', ['company' => $company->slug]),
                        'lastmod'    => $company->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority'   => '0.6',
                    ];
                }
            });

        return $entries;
    }

    /**
     * @return array<string, string> locale => url
     */
    private function localizedUrls(array $locales, string $route, array $attributes = []): array
    {
        $urls = [];

        foreach ($locales as $locale) {
            $urls[$locale] = LaravelLocalization::getURLFromRouteNameTranslated($locale, $route, $attributes);
        }

        return $urls;
    }

    /**
     * @return array<string, string> locale => url
     */
    private function homeUrls(array $locales): array
    {
        $urls = [];

        foreach ($locales as $locale) {
            $urls[$locale] = url($locale);
        }

        return $urls;
    }
}
