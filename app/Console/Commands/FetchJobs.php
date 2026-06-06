<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Technology;
use App\Services\AdzunaService;
use App\Services\Contracts\JobSourceInterface;
use App\Services\JoobleService;
use App\Services\ScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class FetchJobs extends Command
{
    protected $signature = 'jobs:fetch
                            {query : Keyword to search for}
                            {--location= : Optional location filter}
                            {--pages= : Max pages to fetch per source (default: all)}';

    protected $description = 'Fetch job offers from all sources and persist them to the database';

    public function handle(): int
    {
        $query = $this->argument('query');
        $location = $this->option('location');
        $maxPages = $this->option('pages') ? (int) $this->option('pages') : null;

        $sources = $this->resolveSources();
        $technologies = Technology::all()->keyBy(fn ($t) => strtolower($t->name));

        $total = 0;

        foreach ($sources as $name => $source) {
            $this->info("Fetching from {$name}...");

            try {
                $raw = $source->fetchAll($query, $location, $maxPages);
            } catch (\Throwable $e) {
                $this->error("  Failed: {$e->getMessage()}");
                continue;
            }

            $count = 0;

            foreach ($raw as $item) {
                $normalized = $source->normalize($item);

                if (empty($normalized['url'])) {
                    continue;
                }

                $company = $this->resolveCompany($normalized['company'] ?? null);

                $offer = JobOffer::firstOrCreate(
                    ['url' => $normalized['url']],
                    [
                        'company_id'   => $company?->id,
                        'title'        => $normalized['title'],
                        'description'  => $normalized['description'],
                        'source'       => $normalized['source'],
                        'published_at' => $normalized['published_at'],
                    ]
                );

                if ($offer->wasRecentlyCreated) {
                    $matched = $this->detectTechnologies($normalized, $technologies);
                    if ($matched->isNotEmpty()) {
                        $offer->technologies()->attach($matched->pluck('id'));
                    }
                }

                $count++;
            }

            $this->line("  {$count} offers processed.");
            $total += $count;
        }

        $this->info("Done. Total: {$total} offers processed.");

        return self::SUCCESS;
    }

    private function resolveSources(): array
    {
        $sources = [
            'adzuna' => app(AdzunaService::class),
            'jooble' => app(JoobleService::class),
        ];

        foreach (config('scrapers', []) as $name => $config) {
            $sources[$name] = new ScraperService($config);
        }

        return $sources;
    }

    private function detectTechnologies(array $normalized, Collection $technologies): Collection
    {
        $text = strtolower(($normalized['title'] ?? '') . ' ' . ($normalized['description'] ?? ''));

        return $technologies->filter(fn ($tech) => str_contains($text, strtolower($tech->name)));
    }

    private function resolveCompany(?string $name): ?Company
    {
        if (blank($name)) {
            return null;
        }

        return Company::firstOrCreate(['name' => $name]);
    }
}
