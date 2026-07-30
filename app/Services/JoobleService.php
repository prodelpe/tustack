<?php

namespace App\Services;

use App\Actions\ParseSalaryStringAction;
use App\DTOs\NormalizedJobOfferDTO;
use App\Services\Contracts\JobSourceInterface;
use App\Support\JobUrl;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class JoobleService implements JobSourceInterface
{
    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function search(string $query, ?string $location = null, int $page = 1): array
    {
        $body = [
            'keywords'     => $query,
            'page'         => $page,
            'resultonpage' => config('jooble.results_per_page'),
        ];

        if ($location) {
            $body['location'] = $location;
        }

        $response = Http::withHeader('Content-Type', 'application/json')
            ->timeout(15)
            ->post(config('jooble.base_url') . config('jooble.api_key'), $body);

        $response->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null): array
    {
        $offers = [];
        $page   = 1;

        do {
            $data    = $this->search($query, $location, $page);
            $results = $data['jobs'] ?? [];
            $offers  = array_merge($offers, $results);
            $total   = $data['totalCount'] ?? 0;
            $page++;
        } while (
            count($offers) < $total && count($results) > 0
            && ($maxPages === null || $page <= $maxPages)
        );

        return $offers;
    }

    /**
     * @throws Exception
     */
    public function normalize(array $raw): NormalizedJobOfferDTO
    {
        $locationParts = array_map('trim', explode(',', $raw['location'] ?? ''));
        $salary        = app(ParseSalaryStringAction::class)->handle($raw['salary'] ?? null);

        return new NormalizedJobOfferDTO(
            url:               JobUrl::canonical($raw['link'] ?? null),
            source:            'jooble',
            title:             $raw['title'] ?? null,
            company:           $raw['company'] ?? null,
            location:          $raw['location'] ?? null,
            city:              $locationParts[0] ?: null,
            province:          $locationParts[1] ?? null,
            country:           'Spain',
            latitude:          null,
            longitude:         null,
            salaryMin:         $salary['min'],
            salaryMax:         $salary['max'],
            salaryIsPredicted: null,
            description:       $raw['snippet'] ?? null,
            publishedAt:       isset($raw['updated']) ? substr($raw['updated'], 0, 10) : null,
        );
    }
}
