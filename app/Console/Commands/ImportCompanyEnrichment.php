<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Support\CompanyEnrichmentSnapshot;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ImportCompanyEnrichment extends Command
{
    protected $signature = 'enrichment:import
                            {--force : Overwrite values already present in the database}';

    protected $description = 'Restore the enrichment snapshot onto the companies of the current database';

    public function handle(): int
    {
        $snapshot = CompanyEnrichmentSnapshot::read();

        if (empty($snapshot)) {
            $this->warn('No snapshot found at ' . CompanyEnrichmentSnapshot::path());
            return self::SUCCESS;
        }

        $force    = (bool) $this->option('force');
        $matched  = 0;
        $restored = 0;

        $bar = $this->output->createProgressBar(count($snapshot));
        $bar->start();

        // A bulk restore would fire one Meilisearch request per company.
        Company::withoutSyncingToSearch(function () use ($snapshot, $force, &$matched, &$restored, $bar) {
            collect($snapshot)->chunk(500)->each(function (Collection $chunk) use ($force, &$matched, &$restored, $bar) {
                $entries = $chunk->keyBy(function (array $entry, string $name) {
                    return CompanyEnrichmentSnapshot::key($name);
                });

                $companies = Company::query()->whereIn('name_normalized', $entries->keys())->get();

                foreach ($companies as $company) {
                    $entry = $entries->get(CompanyEnrichmentSnapshot::key($company->name));

                    if ($entry === null) {
                        continue;
                    }

                    $matched++;

                    $attributes = CompanyEnrichmentSnapshot::attributesFor($company, $entry, $force);

                    if (! empty($attributes)) {
                        $company->update($attributes);
                        $restored++;
                    }
                }

                $bar->advance($chunk->count());
            });
        });

        $bar->finish();
        $this->newLine(2);

        $missing = count($snapshot) - $matched;

        $this->info("Matched {$matched} of " . count($snapshot) . " companies, {$restored} updated.");

        if ($missing > 0) {
            $this->line("<fg=gray>{$missing} companies in the snapshot are not in the database yet — run jobs:fetch and import again.</>");
        }

        if ($restored > 0) {
            $this->line('<fg=yellow>Run scout:import to reindex the restored companies.</>');
        }

        return self::SUCCESS;
    }
}
