<?php

namespace App\Services;

use App\Actions\ParseSalaryStringAction;
use App\DTOs\NormalizedJobOfferDTO;
use App\Services\Contracts\JobSourceInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use Throwable;

class TecnoempleoService implements JobSourceInterface
{
    private const BASE_URL = 'https://www.tecnoempleo.com/busqueda-empleo.php';

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function search(string $query, ?string $location = null, int $page = 1): array
    {
        $params = ['te' => $query, 'pg' => $page];

        if ($location) {
            $params['provincia'] = $location;
        }

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (compatible; FindYourDevStack/1.0)',
            'Accept'     => 'text/html,application/xhtml+xml',
        ])
            ->timeout(15)
            ->get(self::BASE_URL, $params);

        $response->throw();

        return $this->parse($response->body());
    }

    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null): array
    {
        $offers = [];
        $page   = 1;

        do {
            $results = $this->search($query, $location, $page);
            $offers  = array_merge($offers, $results);
            $page++;
        } while (
            count($results) > 0
            && ($maxPages === null || $page <= $maxPages)
        );

        return $offers;
    }

    public function normalize(array $raw): NormalizedJobOfferDTO
    {
        $salary = app(ParseSalaryStringAction::class)->handle($raw['salary'] ?? null);

        preg_match('/^([^(]+)/', $raw['location'] ?? '', $cityMatch);
        $city = trim(str_replace('y otras', '', $cityMatch[1] ?? '')) ?: null;

        return new NormalizedJobOfferDTO(
            url:               $raw['url'] ?? '',
            source:            'tecnoempleo',
            title:             $raw['title'] ?? null,
            company:           $raw['company'] ?? null,
            location:          $raw['location'] ?? null,
            city:              $city,
            province:          null,
            country:           'Spain',
            latitude:          null,
            longitude:         null,
            salaryMin:         $salary['min'],
            salaryMax:         $salary['max'],
            salaryIsPredicted: null,
            description:       $raw['description'] ?? null,
            publishedAt:       $raw['published_at'] ?? null,
        );
    }

    private function parse(string $html): array
    {
        $crawler = new Crawler($html);
        $offers  = [];

        $crawler->filter('div.p-3.border.rounded.mb-3.bg-white')->each(function (Crawler $card) use (&$offers) {
            try {
                $url     = $card->filter('h3 a')->attr('href');
                $title   = trim($card->filter('h3 a')->text());
                $company = trim($card->filter('a.text-primary')->text());

                $infoCol     = $card->filter('div.col-12.col-lg-3');
                $infoText    = $infoCol->count() ? $infoCol->text() : '';
                $infoHtml    = $infoCol->count() ? $infoCol->html() : '';

                // Date: DD/MM/YYYY → YYYY-MM-DD
                $publishedAt = null;
                if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})/', $infoText, $m)) {
                    $publishedAt = "{$m[3]}-{$m[2]}-{$m[1]}";
                }

                // Location: <b>City</b> (Mode) y otras
                $location = null;
                if (preg_match('/<b>([^<]+)<\/b>(\s*\([^)]+\))?/', $infoHtml, $m)) {
                    $location = trim($m[1] . ($m[2] ?? ''));
                }

                // Salary: "33.000€ - 33.000€ b/a"
                $salary = null;
                if (preg_match('/[\d.,]+\s*€[^<\n]*/', $infoText, $m)) {
                    $salary = trim($m[0]);
                }

                // Description: strip tech badge spans
                $description = null;
                $descNode    = $card->filter('span.hidden-md-down');
                if ($descNode->count()) {
                    $clean       = preg_replace('/<span[^>]*class="badge[^"]*"[^>]*>.*?<\/span>/s', '', $descNode->html());
                    $description = trim(strip_tags($clean)) ?: null;
                }

                $offers[] = [
                    'url'          => $url,
                    'title'        => $title,
                    'company'      => $company,
                    'location'     => $location,
                    'salary'       => $salary,
                    'description'  => $description,
                    'published_at' => $publishedAt,
                ];
            } catch (Throwable) {
                // Skip malformed cards
            }
        });

        return $offers;
    }
}
