<?php

namespace App\Console\Commands;

use App\Jobs\FetchJobOffersJob;
use App\Models\CommandLog;
use App\Models\Technology;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class FetchJobs extends Command
{
    protected $signature = 'jobs:fetch
                            {query? : Keyword to search for}
                            {--all : Fetch for every technology in the database}
                            {--pages= : Max pages to fetch per source (default: all)}';

    protected $description = 'Fetch job offers from all sources in parallel via queue';

    public function handle(): int
    {
        $queries = $this->resolveQueries();

        if (empty($queries)) {
            $this->error('Provide a query or use --all.');
            return self::FAILURE;
        }

        $maxPages = $this->option('pages') ? (int) $this->option('pages') : null;

        $jobs = collect($queries)
            ->map(fn (string $query) => new FetchJobOffersJob($query, $maxPages))
            ->all();

        $log = CommandLog::create([
            'command'    => 'jobs:fetch',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        $this->info('Dispatching ' . count($jobs) . ' jobs to queue...');

        $batch = Bus::batch($jobs)
            ->name('jobs:fetch')
            ->allowFailures()
            ->dispatch();

        $bar = $this->output->createProgressBar(count($jobs));
        $bar->start();

        while (! $batch->finished()) {
            sleep(2);
            $batch = Bus::findBatch($batch->id);
            $bar->setProgress($batch->processedJobs());
        }

        $bar->finish();
        $this->newLine();

        $failed = $batch->failedJobs;
        $this->info("Done. {$batch->totalJobs} queries processed, {$failed} failed.");

        $log->update([
            'status'      => $failed > 0 ? 'partial' : 'success',
            'finished_at' => now(),
            'stats'       => ['total_queries' => $batch->totalJobs, 'failed' => $failed],
        ]);

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
}
