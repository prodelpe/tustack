<?php

namespace App\Services;

use App\Services\Contracts\JobSourceInterface;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class ScraperService implements JobSourceInterface
{
    public function __construct(private array $config) {}

    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null): array
    {
        $offers = [];
        $page = 1;
        $crawler = null;

        do {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ])->timeout(15)->get($this->buildUrl($query, $location, $page));

            $response->throw();

            $crawler = new Crawler($response->body());
            $results = $this->extractOffers($crawler);
            $offers = array_merge($offers, $results);
            $page++;
        } while (count($results) > 0 && $this->hasNextPage($crawler) && ($maxPages === null || $page <= $maxPages));

        return $offers;
    }

    public function normalize(array $raw): array
    {
        return [
            'title'        => $raw['title'] ?? null,
            'company'      => $raw['company'] ?? null,
            'location'     => $raw['location'] ?? null,
            'description'  => $raw['description'] ?? null,
            'url'          => $raw['url'] ?? null,
            'published_at' => isset($raw['date']) ? $this->parseDate($raw['date']) : null,
            'source'       => $this->config['source'],
        ];
    }

    private function buildUrl(string $query, ?string $location, int $page): string
    {
        $url = str_replace('{query}', urlencode($query), $this->config['url_pattern']);

        $params = [];

        if ($location && ! empty($this->config['location_param'])) {
            $params[$this->config['location_param']] = $location;
        }

        if ($page > 1 && ! empty($this->config['pagination_param'])) {
            $params[$this->config['pagination_param']] = $page;
        }

        if ($params) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($params);
        }

        return $url;
    }

    private function extractOffers(Crawler $crawler): array
    {
        $offers = [];

        $crawler->filter($this->config['selector_offer'])->each(function (Crawler $node) use (&$offers) {
            $titleNode = $node->filter($this->config['selector_title']);

            if (! $titleNode->count()) {
                return;
            }

            $url = $titleNode->attr('href');
            if ($url && ! empty($this->config['url_prefix'])) {
                $url = $this->config['url_prefix'] . $url;
            }

            $offers[] = [
                'title'       => trim($titleNode->text()),
                'company'     => $this->extractText($node, 'selector_company'),
                'location'    => $this->extractText($node, 'selector_location'),
                'description' => $this->extractText($node, 'selector_description'),
                'url'         => $url,
                'date'        => $this->extractDate($node),
            ];
        });

        return $offers;
    }

    private function extractText(Crawler $node, string $selectorKey): ?string
    {
        $selector = $this->config[$selectorKey] ?? null;
        if (! $selector) {
            return null;
        }

        $found = $node->filter($selector);

        return $found->count() ? trim($found->text()) : null;
    }

    private function extractDate(Crawler $node): ?string
    {
        $selector = $this->config['selector_date'] ?? null;
        if (! $selector) {
            return null;
        }

        $found = $node->filter($selector);
        if (! $found->count()) {
            return null;
        }

        $text = trim($found->text());

        if (! empty($this->config['date_regex'])) {
            preg_match($this->config['date_regex'], $text, $matches);
            return $matches[1] ?? null;
        }

        return $text;
    }

    private function parseDate(string $date): ?string
    {
        $format = $this->config['date_format'] ?? null;
        if (! $format) {
            return null;
        }

        $parsed = \DateTime::createFromFormat($format, trim($date));

        return $parsed ? $parsed->format('Y-m-d') : null;
    }

    private function hasNextPage(Crawler $crawler): bool
    {
        $selector = $this->config['selector_next_page'] ?? 'a[rel="next"]';

        return $crawler->filter($selector)->count() > 0;
    }
}
