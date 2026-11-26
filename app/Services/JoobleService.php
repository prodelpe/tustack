<?php

namespace App\Services;

use App\DTOs\NormalizedJobOfferDTO;
use App\Enums\JobSource;
use App\Exceptions\JoobleQuotaExceededException;
use App\Services\Contracts\JobSourceInterface;
use App\Support\JobUrl;
use App\Support\Salary;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JoobleService implements JobSourceInterface
{
    private const QUOTA_EXHAUSTED_KEY = 'jooble:quota-exhausted';

    private const QUOTA_ERROR_CODE = 5;

    /**
     * @throws RequestException
     * @throws ConnectionException
     * @throws JoobleQuotaExceededException
     */
    public function search(string $query, ?string $location = null, int $page = 1, ?int $sinceDays = null): array
    {
        if (self::quotaIsExhausted()) {
            throw new JoobleQuotaExceededException;
        }

        $body = [
            'keywords'     => $query,
            'page'         => $page,
            'resultonpage' => config('jooble.results_per_page'),
        ];

        if ($location) {
            $body['location'] = $location;
        }

        if ($sinceDays !== null) {
            $body['datecreatedfrom'] = now()->subDays($sinceDays)->toDateString();
        }

        $response = Http::withHeader('Content-Type', 'application/json')
            ->timeout(15)
            ->post(config('jooble.base_url') . config('jooble.api_key'), $body);

        if ($response->status() === 429 && $response->json('errorCode') === self::QUOTA_ERROR_CODE) {
            $this->markQuotaAsExhausted();

            throw new JoobleQuotaExceededException;
        }

        $response->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     * @throws JoobleQuotaExceededException
     */
    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null, ?int $sinceDays = null): array
    {
        $offers = [];
        $page   = 1;

        do {
            try {
                $data = $this->search($query, $location, $page, $sinceDays);
            } catch (JoobleQuotaExceededException $e) {
                if ($offers === []) {
                    throw $e;
                }

                return $offers;
            }

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

    public static function quotaIsExhausted(): bool
    {
        return Cache::has(self::QUOTA_EXHAUSTED_KEY);
    }

    private function markQuotaAsExhausted(): void
    {
        if (Cache::add(self::QUOTA_EXHAUSTED_KEY, true, now()->endOfDay())) {
            Log::warning('Jooble daily request limit reached: Jooble is skipped until tomorrow');
        }
    }

    /**
     * @throws Exception
     */
    public function normalize(array $raw): NormalizedJobOfferDTO
    {
        $locationParts = array_map('trim', explode(',', $raw['location'] ?? ''));
        $salary        = Salary::parse($raw['salary'] ?? null);

        return new NormalizedJobOfferDTO(
            url:               JobUrl::canonical($raw['link'] ?? null),
            source:            JobSource::Jooble,
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
