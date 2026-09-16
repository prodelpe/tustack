<?php

namespace App\Console\Commands;

use App\Jobs\EnrichCompanyJob;
use App\Models\CommandLog;
use App\Models\Company;
use App\Support\Gemini;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;

class EnrichCompanies extends Command
{
    protected $signature = 'companies:enrich
                            {--limit=0 : Max companies to process (0 = all)}
                            {--min-technologies=2 : Skip companies with fewer distinct technologies}
                            {--reset : Re-process already enriched companies}
                            {--estimate : Show estimated cost without processing}
                            {--no-snapshot : Skip updating the enrichment snapshot afterwards}';

    protected $description = 'Enrich companies with Gemini (description, sector, employees, website)';

    public function handle(): int
    {
        $limit            = (int) $this->option('limit');
        $minTechnologies  = (int) $this->option('min-technologies');

        $query = Company::with(['jobOffers.technologies', 'province'])
            ->when(! $this->option('reset'), fn ($q) => $q->where('gemini_enriched', false))
            ->when($minTechnologies > 1, fn ($q) => $q->withMinimumTechnologies($minTechnologies));

        $total = $query->count();

        if ($total === 0) {
            $this->info('No companies to enrich.');
            return self::SUCCESS;
        }

        if ($limit > 0) {
            $total = min($total, $limit);
        }

        // Estimating is what you do while it is off, so only the spending is
        // stopped here. Without this the batch would dispatch and every job
        // would fail quietly, one by one.
        if (! $this->option('estimate') && ! Gemini::isEnabled()) {
            $this->error(Gemini::whyItIsOff());
            $this->line('<fg=gray>Run with --estimate to see the cost without spending anything.</>');

            return self::FAILURE;
        }

        if ($this->option('estimate')) {
            $cost = $total * 0.00111;
            $this->info("Estimate: {$total} companies × €0.00111 (Gemini) ≈ €" . number_format($cost, 2));
            $this->line("<fg=gray>Only companies with {$minTechnologies}+ distinct technologies are counted.</>");
            $this->line('<fg=yellow>Google Translate cost is negligible (<€0.01 extra).</>');
            $this->line('Run without --estimate to process.');
            return self::SUCCESS;
        }

        if (! $this->hasQueueWorkers()) {
            $this->error('No queue workers detected. Start them first:');
            $this->line('  php artisan horizon');
            return self::FAILURE;
        }

        $companyIds = $query
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->pluck('id');

        $jobs = $companyIds
            ->map(fn (int $id) => new EnrichCompanyJob($id))
            ->all();

        $this->info("Dispatching {$total} enrichment jobs...");

        $log = CommandLog::create([
            'command'    => 'companies:enrich',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        $batch = Bus::batch($jobs)
            ->name('companies:enrich')
            ->allowFailures()
            ->dispatch();

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        while (! $batch->finished()) {
            sleep(2);
            $batch = Bus::findBatch($batch->id);
            $bar->setProgress($batch->processedJobs());
        }

        $bar->finish();
        $this->newLine();

        $failed   = $batch->failedJobs;
        $enriched = $this->enrichedSince($companyIds, $log->started_at);

        $this->info("Done. {$enriched} of {$batch->totalJobs} companies enriched, {$failed} jobs failed.");

        if ($enriched === 0) {
            $this->error('Nothing was enriched. If GEMINI_ENABLED was just switched on, restart Horizon: php artisan horizon:terminate');
        }

        $log->update([
            'status'      => $this->status($enriched, $failed),
            'finished_at' => now(),
            'stats'       => ['total' => $batch->totalJobs, 'failed' => $failed, 'enriched' => $enriched],
        ]);

        if (! $this->option('no-snapshot')) {
            $this->call('enrichment:export');
        }

        return self::SUCCESS;
    }

    /**
     * Counted from the companies themselves: a finished job only means the
     * worker got through it, which is how a batch that enriched nothing once
     * reported success.
     *
     * @param  Collection<int, int>  $companyIds
     */
    private function enrichedSince(Collection $companyIds, Carbon $since): int
    {
        return Company::query()
            ->whereIn('id', $companyIds)
            ->where('gemini_enriched', true)
            ->where('updated_at', '>=', $since)
            ->count();
    }

    private function status(int $enriched, int $failed): string
    {
        if ($enriched === 0) {
            return 'failed';
        }

        return $failed > 0 ? 'partial' : 'success';
    }

    private function hasQueueWorkers(): bool
    {
        $output = shell_exec('ps aux | grep "[q]ueue:work\|[h]orizon"');
        return ! empty(trim($output ?? ''));
    }
}
