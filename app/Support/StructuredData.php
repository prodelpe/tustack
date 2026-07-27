<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Province;
use App\Models\Technology;
use Illuminate\Support\Collection;

/**
 * schema.org descriptions of what each page is about. Search engines use them
 * to understand the page instead of guessing, and to build richer results.
 */
class StructuredData
{
    public static function website(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'      => 'WebSite',
                    '@id'        => url('/') . '#website',
                    'url'        => url('/'),
                    'name'       => 'TuStack',
                    'inLanguage' => app()->getLocale(),
                    'publisher'  => ['@id' => url('/') . '#organization'],
                    'potentialAction' => [
                        '@type'  => 'SearchAction',
                        'target' => [
                            '@type'       => 'EntryPoint',
                            'urlTemplate' => route('home') . '?q={search_term_string}',
                        ],
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@type'       => 'Organization',
                    '@id'         => url('/') . '#organization',
                    'name'        => 'TuStack',
                    'url'         => url('/'),
                    'description' => __('seo.default_description'),
                ],
            ],
        ];
    }

    public static function company(Company $company, Collection $technologies, string $description): array
    {
        $organization = array_filter([
            '@type'       => 'Organization',
            'name'        => $company->name,
            'description' => $description,
            'url'         => $company->website,
            'address'     => self::address($company),
            'geo'         => self::geo($company),
            'knowsAbout'  => $technologies->pluck('name')->values()->all(),
        ]);

        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                $organization + ['mainEntityOfPage' => ['@type' => 'WebPage', '@id' => url()->current()]],
                self::breadcrumb([$company->name => url()->current()]),
            ],
        ];
    }

    public static function landing(string $heading, Technology $technology, ?Province $province, Collection $companies, int $total): array
    {
        $items = $companies->values()->map(function (Company $company, int $index) {
            return [
                '@type'    => 'ListItem',
                'position' => $index + 1,
                'name'     => $company->name,
                'url'      => route('companies.show', $company),
            ];
        })->all();

        return [
            '@context' => 'https://schema.org',
            '@graph'   => [
                [
                    '@type'           => 'ItemList',
                    'name'            => $heading,
                    'numberOfItems'   => $total,
                    'itemListElement' => $items,
                ],
                self::breadcrumb([$heading => url()->current()]),
            ],
        ];
    }

    /**
     * @param array<string, string> $trail label => url
     */
    private static function breadcrumb(array $trail): array
    {
        $items = [[
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'TuStack',
            'item'     => route('home'),
        ]];

        $position = 2;

        foreach ($trail as $label => $url) {
            $items[] = [
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $label,
                'item'     => $url,
            ];
        }

        return [
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private static function address(Company $company): ?array
    {
        if (blank($company->city) && ! $company->province) {
            return null;
        }

        return array_filter([
            '@type'           => 'PostalAddress',
            'addressLocality' => $company->city,
            'addressRegion'   => $company->province?->name,
            'addressCountry'  => 'ES',
        ]);
    }

    private static function geo(Company $company): ?array
    {
        if (blank($company->latitude) || blank($company->longitude)) {
            return null;
        }

        return [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $company->latitude,
            'longitude' => (float) $company->longitude,
        ];
    }
}
