<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class AdzunaService
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

    public function fetchAll(string $query, ?string $location = null): array
    {
        $offers = [];
        $page = 1;

        do {
            $data = $this->search($query, $location, $page);
            $results = $data['results'] ?? [];
            $offers = array_merge($offers, $results);
            $total = $data['count'] ?? 0;
            $page++;
        } while (count($offers) < $total && count($results) > 0);

        return $offers;
    }
}
