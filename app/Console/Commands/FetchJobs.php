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
                            {query? : Keyword to search for}
                            {--all : Fetch for every technology in the database}
                            {--location= : Optional location filter}
                            {--pages= : Max pages to fetch per source (default: all)}';

    protected $description = 'Fetch job offers from all sources and persist them to the database';

    public function handle(ProcessJobOfferAction $processJobOffer): int
    {
        $location = $this->option('location');
        $maxPages = $this->option('pages') ? (int) $this->option('pages') : null;

        $queries = $this->resolveQueries();

        if (empty($queries)) {
            $this->error('Provide a query or use --all.');
            return self::FAILURE;
        }

        $sources      = $this->resolveSources();
        $technologies = Technology::all()->keyBy(fn ($t) => strtolower($t->name));
        $total        = 0;

        foreach ($queries as $query) {
            $this->line("\n<fg=cyan>Query: {$query}</>");

            foreach ($sources as $name => $source) {
                $this->info("  [{$name}] Fetching...");

                try {
                    $raw = $source->fetchAll($query, $location, $maxPages);
                } catch (\Throwable $e) {
                    $this->error("  [{$name}] Failed: {$e->getMessage()}");
                    continue;
                }

                $count = 0;
                foreach ($raw as $item) {
                    if ($processJobOffer->handle($item, $source, $technologies)) {
                        $count++;
                    }
                }

                $this->line("  [{$name}] {$count} offers processed.");
                $total += $count;
            }
        }

        $this->info("\nDone. Total: {$total} offers processed.");

        return self::SUCCESS;
    }

    private function resolveQueries(): array
    {
        if ($this->option('all')) {
            return Technology::orderBy('name')->pluck('name')->all();
        }

        $query = $this->argument('query');

        return $query ? [$query] : [];
    }

    private function resolveSources(): array
    {
        $sources = [
            'adzuna'      => app(AdzunaService::class),
            'jooble'      => app(JoobleService::class),
            'tecnoempleo' => app(TecnoempleoService::class),
        ];


        return $sources;
    }
}
