<?php

namespace App\Console\Commands;

use App\Actions\ResolveProvinceAction;
use App\Models\Company;
use Illuminate\Console\Command;

class ResolveCompanyProvinces extends Command
{
    protected $signature = 'companies:resolve-provinces
                            {--dry-run : Report what would change without saving anything}';

    protected $description = 'Fill in the province of companies that do not have one, from their city and location';

    public function handle(ResolveProvinceAction $resolveProvince): int
    {
        $companies = Company::query()->whereNull('province_id')->get();

        if ($companies->isEmpty()) {
            $this->info('Every company already has a province.');

            return self::SUCCESS;
        }

        $dryRun     = (bool) $this->option('dry-run');
        $resolved   = 0;
        $unresolved = [];

        $this->line('Resolving ' . $companies->count() . ' companies without a province...');
        $this->newLine();

        foreach ($companies as $company) {
            $province = $resolveProvince->handle(null, $company->city, $company->location);

            if ($province === null) {
                $city = $company->city ?: '(no city)';
                $unresolved[$city] = ($unresolved[$city] ?? 0) + 1;

                continue;
            }

            if (! $dryRun) {
                $company->update(['province_id' => $province]);
            }

            $resolved++;
        }

        $this->info(($dryRun ? 'Would resolve ' : 'Resolved ') . $resolved . ' of ' . $companies->count() . ' companies.');

        arsort($unresolved);

        foreach (array_slice($unresolved, 0, 8, true) as $city => $count) {
            $this->line('  <fg=gray>' . str_pad((string) $city, 26) . $count . '</>');
        }

        return self::SUCCESS;
    }
}
