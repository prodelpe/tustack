<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Support\CompanyEnrichmentSnapshot;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExportCompanyEnrichment extends Command
{
    protected $signature = 'enrichment:export';

    protected $description = 'Merge the enrichment data of the current database into the versioned snapshot';

    public function handle(): int
    {
        $snapshot = CompanyEnrichmentSnapshot::read();
        $before   = count($snapshot);
        $changed  = 0;

        // "AirHelp" must update the existing "Airhelp" entry, not add a second one.
        $index = [];

        foreach (array_keys($snapshot) as $name) {
            $index[CompanyEnrichmentSnapshot::key($name)] = $name;
        }

        $this->enrichedCompanies()->chunkById(500, function (Collection $companies) use (&$snapshot, &$index, &$changed) {
            foreach ($companies as $company) {
                $key     = CompanyEnrichmentSnapshot::key($company->name);
                $name    = $index[$key] ?? $company->name;
                $current = $snapshot[$name] ?? null;
                $merged  = CompanyEnrichmentSnapshot::merge($current, $company);

                if ($merged !== $current) {
                    $changed++;
                }

                $snapshot[$name] = $merged;
                $index[$key]     = $name;
            }
        });

        if ($changed === 0) {
            $this->info("Snapshot already up to date ({$before} companies).");
            return self::SUCCESS;
        }

        CompanyEnrichmentSnapshot::write($snapshot);

        $added = count($snapshot) - $before;

        $this->info("Snapshot written: " . count($snapshot) . " companies ({$added} new, " . ($changed - $added) . " updated).");
        $this->line('<fg=yellow>Commit ' . CompanyEnrichmentSnapshot::path() . ' to keep the enrichment safe.</>');

        return self::SUCCESS;
    }

    private function enrichedCompanies(): Builder
    {
        return Company::query()->where(function (Builder $query) {
            $query->where('gemini_enriched', true)
                ->orWhereNotNull('description')
                ->orWhereNotNull('sector')
                ->orWhereNotNull('employees')
                ->orWhereNotNull('website')
                ->orWhereNotNull('latitude');
        });
    }
}
