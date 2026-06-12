<?php

namespace App\Console\Commands;

use App\Actions\ProcessJobOfferAction;
use App\Models\Technology;
use App\Services\AdzunaService;
use App\Services\JoobleService;
use App\Services\TecnoempleoService;
use Illuminate\Console\Command;

class FetchJobs extends Command
{
    protected $signature = 'jobs:fetch
                            {query : Keyword to search for}
                            {--location= : Optional location filter}
                            {--pages= : Max pages to fetch per source (default: all)}';

    protected $description = 'Fetch job offers from all sources and persist them to the database';

    public function handle(ProcessJobOfferAction $processJobOffer): int
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
                if ($processJobOffer->handle($item, $source, $technologies)) {
                    $count++;
                }
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
            'adzuna'      => app(AdzunaService::class),
            'jooble'      => app(JoobleService::class),
            'tecnoempleo' => app(TecnoempleoService::class),
        ];

        // TODO: enable scrapers once final pipeline is validated
        // foreach (config('scrapers', []) as $name => $config) {
        //     $sources[$name] = new ScraperService($config);
        // }

        return $sources;
    }
}
