<?php

namespace App\Console\Commands;

use App\Jobs\EnrichCompanyJob;
use App\Models\CommandLog;
use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class EnrichCompanies extends Command
{
    protected $signature = 'companies:enrich
                            {--limit=0 : Max companies to process (0 = all)}
                            {--reset : Re-process already enriched companies}
                            {--estimate : Show estimated cost without processing}
                            {--no-snapshot : Skip updating the enrichment snapshot afterwards}';

    protected $description = 'Enrich companies with Gemini (description, sector, employees, website)';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $query = Company::with(['jobOffers.technologies', 'province'])
            ->when(! $this->option('reset'), fn ($q) => $q->where('gemini_enriched', false));

        $total = $query->count();

        if ($total === 0) {
            $this->info('No companies to enrich.');
            return self::SUCCESS;
        }

        if ($limit > 0) {
            $total = min($total, $limit);
        }

        if ($this->option('estimate')) {
            $cost = $total * 0.00111;
            $this->info("Estimate: {$total} companies × €0.00111 (Gemini) ≈ €" . number_format($cost, 2));
            $this->line('<fg=yellow>Google Translate cost is negligible (<€0.01 extra).</>');
            $this->line('Run without --estimate to process.');
            return self::SUCCESS;
        }

        if (! $this->hasQueueWorkers()) {
            $this->error('No queue workers detected. Start them first:');
            $this->line('  php artisan horizon');
            return self::FAILURE;
        }

        $jobs = $query
            ->when($limit > 0, fn ($q) => $q->limit($limit))
            ->pluck('id')
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

        $failed = $batch->failedJobs;
        $this->info("Done. {$batch->totalJobs} companies processed, {$failed} failed.");

        $log->update([
            'status'      => $failed > 0 ? 'partial' : 'success',
            'finished_at' => now(),
            'stats'       => ['total' => $batch->totalJobs, 'failed' => $failed],
        ]);

        if (! $this->option('no-snapshot')) {
            $this->call('enrichment:export');
        }

        return self::SUCCESS;
    }

    private function hasQueueWorkers(): bool
    {
        $output = shell_exec('ps aux | grep "[q]ueue:work\|[h]orizon"');
        return ! empty(trim($output ?? ''));
    }
}
