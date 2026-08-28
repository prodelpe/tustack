<?php

namespace App\Console\Commands;

use App\Actions\AskGeminiAboutCompanyNamesAction;
use App\Actions\FindCompanyAliasCandidatesAction;
use App\Actions\MergeCompaniesAction;
use App\Models\Company;
use App\Models\CompanyAlias;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class FindCompanyAliases extends Command
{
    protected $signature = 'companies:find-aliases
                            {--dry-run : Ask and report, without merging or remembering anything}
                            {--limit= : Pairs to ask about in this run (defaults to the configured ceiling)}
                            {--sweep : Also read the whole catalogue looking for pairs no rule can see}';

    protected $description = 'Reunite companies split across several spellings of their name';

    public function handle(
        FindCompanyAliasCandidatesAction $findCandidates,
        AskGeminiAboutCompanyNamesAction $gemini,
        MergeCompaniesAction $merge,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $limit  = (int) ($this->option('limit') ?: config('companies.alias_max_pairs_per_run'));

        $candidates = $findCandidates->handle($limit);

        if ($candidates->isEmpty() && ! $this->option('sweep')) {
            $this->info('No new company names to ask about.');

            return self::SUCCESS;
        }

        $merged = $this->judgeCandidates($candidates, $gemini, $merge, $dryRun);

        if ($merged === null) {
            $this->error('Gemini did not answer. Nothing was recorded; run again later.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(($dryRun ? 'Would merge ' : 'Merged ') . $merged . ' of ' . $candidates->count() . ' candidate pairs.');

        if ($this->option('sweep')) {
            $this->sweepCatalogue($gemini, $dryRun);
        }

        return self::SUCCESS;
    }

    /** Returns how many pairs were merged, or null if Gemini never answered. */
    private function judgeCandidates(
        Collection $candidates,
        AskGeminiAboutCompanyNamesAction $gemini,
        MergeCompaniesAction $merge,
        bool $dryRun,
    ): ?int {
        $merged   = 0;
        $answered = false;

        foreach ($candidates->chunk(config('companies.alias_batch_size')) as $chunk) {
            $pairs = $chunk
                ->map(fn (array $candidate) => [$candidate['survivor']->name, $candidate['absorbed']->name])
                ->values()
                ->all();

            $verdicts = $gemini->judgePairs($pairs);

            if ($verdicts === null) {
                continue;
            }

            $answered = true;

            foreach ($chunk->values() as $index => $candidate) {
                $same = $verdicts[$index] ?? false;

                $this->line(sprintf(
                    '  %s <fg=gray>%s</> %s <fg=gray>(%s)</>',
                    $same ? '<fg=green>merge </>' : '<fg=gray>keep  </>',
                    str_pad($candidate['survivor']->name, 34),
                    $candidate['absorbed']->name,
                    $candidate['rule'],
                ));

                if ($dryRun) {
                    $merged += $same ? 1 : 0;

                    continue;
                }

                if ($same) {
                    $merge->handle($candidate['absorbed'], $candidate['survivor']);
                    $merged++;

                    continue;
                }

                $this->remember($candidate['absorbed'], $candidate['survivor']);
            }
        }

        return $answered || $candidates->isEmpty() ? $merged : null;
    }

    /**
     * The rules cannot see Telefónica in Movistar, so the catalogue is read in
     * alphabetical blocks. These are never merged on their own: they wait for
     * someone to look at them in the admin.
     */
    private function sweepCatalogue(AskGeminiAboutCompanyNamesAction $gemini, bool $dryRun): void
    {
        $companies = Company::query()->orderBy('name')->get(['id', 'name', 'name_normalized']);
        $proposed  = 0;

        $this->newLine();
        $this->line('Sweeping ' . $companies->count() . ' names for pairs no rule can see...');

        foreach ($companies->chunk(config('companies.alias_sweep_block_size')) as $block) {
            $block = $block->values();
            $pairs = $gemini->findPairsIn($block->pluck('name')->all());

            if (blank($pairs)) {
                continue;
            }

            foreach ($pairs as [$first, $second]) {
                $survivor = $block[$first];
                $absorbed = $block[$second];

                if (mb_strlen($absorbed->name) < mb_strlen($survivor->name)) {
                    [$survivor, $absorbed] = [$absorbed, $survivor];
                }

                $key = CompanyAlias::pairKey($absorbed->name, $survivor->name);

                if (CompanyAlias::query()->where('pair_key', $key)->exists()) {
                    continue;
                }

                $this->line(sprintf(
                    '  <fg=yellow>review</> <fg=gray>%s</> %s',
                    str_pad($survivor->name, 34),
                    $absorbed->name,
                ));

                $proposed++;

                if ($dryRun) {
                    continue;
                }

                CompanyAlias::create([
                    'name'            => $absorbed->name,
                    'name_normalized' => $absorbed->name_normalized,
                    'company_id'      => $survivor->id,
                    'pair_key'        => $key,
                    'status'          => CompanyAlias::PENDING,
                    'source'          => 'gemini',
                ]);
            }
        }

        $this->info($proposed . ' pairs left for review in the admin.');
    }

    /** A no is worth storing too: it is what stops the pair being paid for again. */
    private function remember(Company $absorbed, Company $survivor): void
    {
        CompanyAlias::updateOrCreate(
            ['pair_key' => CompanyAlias::pairKey($absorbed->name, $survivor->name)],
            [
                'name'            => $absorbed->name,
                'name_normalized' => $absorbed->name_normalized,
                'company_id'      => $survivor->id,
                'status'          => CompanyAlias::REJECTED,
                'source'          => 'gemini',
            ]
        );
    }
}
