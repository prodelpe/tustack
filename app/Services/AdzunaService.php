<?php

namespace App\Services;

use App\Services\Contracts\JobSourceInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AdzunaService implements JobSourceInterface
{
    private PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::baseUrl(config('adzuna.base_url'))
            ->timeout(15);
    }

    public function search(string $query, ?string $location = null, int $page = 1): array
    {
        $params = [
            'app_id' => config('adzuna.app_id'),
            'app_key' => config('adzuna.app_key'),
            'results_per_page' => config('adzuna.results_per_page'),
            'what' => $query,
            'content-type' => 'application/json',
        ];

        if ($location) {
            $params['where'] = $location;
        }

        $response = $this->client->get(
            config('adzuna.country') . '/search/' . $page,
            $params
        );

        $response->throw();

        return $response->json();
    }

    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null): array
    {
        $offers = [];
        $page = 1;

        do {
            $data = $this->search($query, $location, $page);
            $results = $data['results'] ?? [];
            $offers = array_merge($offers, $results);
            $total = $data['count'] ?? 0;
            $page++;
        } while (count($offers) < $total && count($results) > 0 && ($maxPages === null || $page <= $maxPages));

        return $offers;
    }

    public function normalize(array $raw): array
    {
        $area = $raw['location']['area'] ?? [];
        $areaCount = count($area);

        return [
            'title'              => $raw['title'] ?? null,
            'company'            => $raw['company']['display_name'] ?? null,
            'location'           => $raw['location']['display_name'] ?? null,
            'country'            => $areaCount >= 1 ? $area[0] : null,
            'province'           => $areaCount >= 3 ? $area[$areaCount - 2] : null,
            'city'               => $areaCount >= 2 ? $area[$areaCount - 1] : null,
            'latitude'           => $raw['latitude'] ?? null,
            'longitude'          => $raw['longitude'] ?? null,
            'salary_min'         => $raw['salary_min'] ?? null,
            'salary_max'         => $raw['salary_max'] ?? null,
            'salary_is_predicted'=> isset($raw['salary_is_predicted']) ? (bool) $raw['salary_is_predicted'] : null,
            'description'        => $raw['description'] ?? null,
            'url'                => $raw['redirect_url'] ?? null,
            'published_at'       => isset($raw['created']) ? substr($raw['created'], 0, 10) : null,
            'source'             => 'adzuna',
        ];
    }
}
