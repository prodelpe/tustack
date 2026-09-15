<?php

namespace App\Services;

use App\DTOs\NormalizedJobOfferDTO;
use App\Services\Contracts\JobSourceInterface;
use App\Support\JobUrl;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class AdzunaService implements JobSourceInterface
{
    private PendingRequest $client;

    /** Waits between attempts when Adzuna is busy, in milliseconds. */
    private const BACKOFF = [2000, 5000, 10000];

    /** Never wait longer than this for one attempt: the whole job has five minutes. */
    private const MAX_WAIT = 30000;

    public function __construct()
    {
        // Adzuna answers 503 and 429 when it is busy or we ask too fast: 26 of
        // 39 queries on the first night in production. Those are worth another
        // try after a pause; anything else, a bad key included, fails at once.
        $this->client = Http::baseUrl(config('adzuna.base_url'))
            ->timeout(15)
            ->retry(
                count(self::BACKOFF) + 1,
                function (int $attempt, Throwable $e) {
                    return $this->waitBeforeRetry($attempt, $e);
                },
                function (Throwable $e) {
                    return $e instanceof RequestException
                        && in_array($e->response->status(), [429, 503], true);
                },
            );
    }

    /** Honours Retry-After when Adzuna sends it, otherwise backs off step by step. */
    private function waitBeforeRetry(int $attempt, Throwable $e): int
    {
        $retryAfter = $e instanceof RequestException ? $e->response->header('Retry-After') : '';

        $wait = is_numeric($retryAfter)
            ? (int) $retryAfter * 1000
            : self::BACKOFF[min($attempt - 1, count(self::BACKOFF) - 1)];

        return min($wait, self::MAX_WAIT);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function search(string $query, ?string $location = null, int $page = 1, ?int $sinceDays = null): array
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

        if ($sinceDays !== null) {
            $params['max_days_old'] = $sinceDays;
        }

        $response = $this->client->get(
            config('adzuna.country') . '/search/' . $page,
            $params
        );

        $response->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null, ?int $sinceDays = null): array
    {
        $offers = [];
        $page = 1;

        do {
            $data = $this->search($query, $location, $page, $sinceDays);
            $results = $data['results'] ?? [];
            $offers = array_merge($offers, $results);
            $total = $data['count'] ?? 0;
            $page++;
        } while (
            count($offers) < $total && count($results) > 0
            && ($maxPages === null || $page <= $maxPages)
        );

        return $offers;
    }

    public function normalize(array $raw): NormalizedJobOfferDTO
    {
        $area = $raw['location']['area'] ?? [];
        $areaCount = count($area);

        return new NormalizedJobOfferDTO(
            url: JobUrl::canonical($raw['redirect_url'] ?? null),
            source: 'adzuna',
            title: $raw['title'] ?? null,
            company: $raw['company']['display_name'] ?? null,
            location: $raw['location']['display_name'] ?? null,
            city: $areaCount >= 2 ? $area[$areaCount - 1] : null,
            province: $areaCount >= 3 ? $area[$areaCount - 2] : null,
            country: $areaCount >= 1 ? $area[0] : null,
            latitude: $raw['latitude'] ?? null,
            longitude: $raw['longitude'] ?? null,
            salaryMin: $raw['salary_min'] ?? null,
            salaryMax: $raw['salary_max'] ?? null,
            salaryIsPredicted: isset($raw['salary_is_predicted']) ? (bool) $raw['salary_is_predicted'] : null,
            description: $raw['description'] ?? null,
            publishedAt: isset($raw['created']) ? substr($raw['created'], 0, 10) : null,
        );
    }
}
