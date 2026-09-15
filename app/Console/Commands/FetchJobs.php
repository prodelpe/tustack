<?php

namespace App\Console\Commands;

use App\Jobs\FetchJobOffersJob;
use App\Models\CommandLog;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Technology;
use App\Models\User;
use App\Notifications\FetchReport;
use App\Support\FetchRun;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class FetchJobs extends Command
{
    protected $signature = 'jobs:fetch
                            {query? : Keyword to search for}
                            {--all : Fetch for every technology in the database}
                            {--pages= : Max pages to fetch per source (default: all)}
                            {--since= : Only offers published in the last N days (default: all)}
                            {--report : Send the result to the admins: Telegram always, mail when something needs a look}';

    protected $description = 'Fetch job offers from all sources in parallel via queue';

    public function handle(): int
    {
        $queries = $this->resolveQueries();

        if (empty($queries)) {
            $this->error('Provide a query or use --all.');
            return self::FAILURE;
        }

        $maxPages  = $this->option('pages') ? (int) $this->option('pages') : null;
        $sinceDays = $this->option('since') ? (int) $this->option('since') : null;

        $jobs = collect($queries)
            ->map(function (string $query) use ($maxPages, $sinceDays) {
                return new FetchJobOffersJob($query, $maxPages, $sinceDays);
            })
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

        // Without workers the batch never moves and this loop would wait
        // forever, silently, with no report ever sent.
        // The default matters: a config cache from before this setting existed
        // reads it as null, and the run gave up the moment it started.
        $deadline = now()->addHours($this->timeoutHours());

        while (! $batch->finished()) {
            if (now()->greaterThan($deadline)) {
                return $this->giveUp($log, $batch);
            }

            sleep(2);
            $batch = Bus::findBatch($batch->id);
            $bar->setProgress($batch->processedJobs());
        }

        $bar->finish();
        $this->newLine();

        $stats = $this->stats($log, $batch);

        $log->update([
            'status'      => $stats['failed'] > 0 || ! empty($stats['source_failures']) || ! empty($stats['offer_failures']) ? 'partial' : 'success',
            'finished_at' => now(),
            'stats'       => $stats,
        ]);

        $this->info("Done. {$stats['total_queries']} queries processed, {$stats['failed']} failed, {$stats['new_offers']} new offers.");

        $this->report($log);

        return self::SUCCESS;
    }

    private function giveUp(CommandLog $log, Batch $batch): int
    {
        $batch->cancel();

        $hours = $this->timeoutHours();

        $log->update([
            'status'        => 'failed',
            'finished_at'   => now(),
            'error_message' => "Gave up after {$hours}h with {$batch->processedJobs()} of {$batch->totalJobs} queries done. Are the queue workers (Horizon) running?",
            'stats'         => $this->stats($log, $batch),
        ]);

        $this->newLine();
        $this->error($log->error_message);

        $this->report($log);

        return self::FAILURE;
    }

    /** What the night actually brought, not only whether the queries ran. */
    private function stats(CommandLog $log, Batch $batch): array
    {
        $since = $log->started_at;

        return [
            'total_queries'        => $batch->totalJobs,
            'failed'               => $batch->failedJobs,
            'source_failures'      => FetchRun::sourceFailures($batch->id),
            'offer_failures'       => FetchRun::offerFailures($batch->id),
            'new_offers'           => JobOffer::query()->where('created_at', '>=', $since)->count(),
            'new_offers_by_source' => JobOffer::query()
                ->where('created_at', '>=', $since)
                ->selectRaw('source, COUNT(*) as total')
                ->groupBy('source')
                ->pluck('total', 'source')
                ->map(function ($total) {
                    return (int) $total;
                })
                ->all(),
            'new_companies'        => Company::query()->where('created_at', '>=', $since)->count(),
            'total_offers'         => JobOffer::query()->count(),
            'total_companies'      => Company::query()->count(),
        ];
    }

    private function timeoutHours(): int
    {
        return (int) config('jobs.fetch_timeout_hours', 8) ?: 8;
    }

    /**
     * The report is the last thing the run does and never the reason it fails:
     * the data is in and logged by now. A broken mailer once took the Telegram
     * message down with it and turned a good night into a crash.
     */
    private function report(CommandLog $log): void
    {
        if (! $this->option('report')) {
            return;
        }

        try {
            Notification::send(
                User::query()->where('is_admin', true)->get(),
                new FetchReport($log->fresh())
            );
        } catch (Throwable $e) {
            Log::error('The fetch report could not be delivered', ['error' => $e->getMessage()]);

            $this->warn('Report not delivered: ' . $e->getMessage());
        }
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
