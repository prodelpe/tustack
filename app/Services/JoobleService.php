<?php

namespace App\Services;

use App\Services\Contracts\JobSourceInterface;
use Illuminate\Support\Facades\Http;

class JoobleService implements JobSourceInterface
{
    public function search(string $query, ?string $location = null, int $page = 1): array
    {
        $body = [
            'keywords' => $query,
            'page' => $page,
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

    public function fetchAll(string $query, ?string $location = null): array
    {
        $offers = [];
        $page = 1;

        do {
            $data = $this->search($query, $location, $page);
            $results = $data['jobs'] ?? [];
            $offers = array_merge($offers, $results);
            $total = $data['totalCount'] ?? 0;
            $page++;
        } while (count($offers) < $total && count($results) > 0);

        return $offers;
    }

    public function normalize(array $raw): array
    {
        return [
            'title'        => $raw['title'] ?? null,
            'company'      => $raw['company'] ?? null,
            'location'     => $raw['location'] ?? null,
            'description'  => $raw['snippet'] ?? null,
            'url'          => $raw['link'] ?? null,
            'published_at' => isset($raw['updated']) ? substr($raw['updated'], 0, 10) : null,
            'source'       => 'jooble',
        ];
    }
}
