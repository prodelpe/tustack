<?php

namespace App\Console\Commands;

use App\Actions\EnrichCompanyWithGeminiAction;
use App\Models\Company;
use Illuminate\Console\Command;

class EnrichCompanies extends Command
{
    protected $signature = 'companies:enrich
                            {--limit=0 : Max companies to process (0 = all)}
                            {--sleep=4 : Seconds between requests}
                            {--reset : Re-process already enriched companies}';

    protected $description = 'Enrich companies with Gemini (description, sector, employees, website)';

    public function handle(EnrichCompanyWithGeminiAction $action): int
    {
        $limit = (int) $this->option('limit');
        $sleep = (int) $this->option('sleep');

        $query = Company::with(['jobOffers.technologies', 'province'])
            ->when(! $this->option('reset'), function ($q)  {
                return $q->where('gemini_enriched', false);
            });

        $total = $query->count();

        if ($total === 0) {
            $this->info('No companies to enrich.');
            return self::SUCCESS;
        }

        if ($limit > 0) {
            $total = min($total, $limit);
        }

        $this->info("Enriching {$total} companies (sleep: {$sleep}s between requests)...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        $succeeded = 0;

        $query->when($limit > 0, fn ($q) => $q->limit($limit))
            ->each(function (Company $company) use ($action, $sleep, $bar, &$processed, &$succeeded) {
                $ok = $action->handle($company);

                if ($ok) {
                    $succeeded++;
                }

                $processed++;
                $bar->advance();

                if ($sleep > 0) {
                    sleep($sleep);
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Done. {$succeeded}/{$processed} companies enriched successfully.");

        return self::SUCCESS;
    }
}
