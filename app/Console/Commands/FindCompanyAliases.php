<?php

namespace App\Console\Commands;

use App\Actions\AskGeminiAboutCompanyNamesAction;
use App\Actions\FindCompanyAliasCandidatesAction;
use App\Actions\MergeCompaniesAction;
use App\Models\Company;
use App\Models\CompanyAlias;
use App\Support\Gemini;
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
        // --dry-run still asks Gemini, so it is stopped here too.
        if (! Gemini::isEnabled()) {
            $this->error(Gemini::whyItIsOff());

            return self::FAILURE;
        }

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

                // The candidates were worked out before the first merge, so by
                // now either side may have been absorbed by an earlier one in
                // this same run: Roche swallows F. Hoffmann-La Roche AG, and
                // the pair that would give F. Hoffmann-La Roche AG the Gruppe
                // points at a company that is gone.
                $survivor = $this->current($candidate['survivor']);
                $absorbed = $this->current($candidate['absorbed']);

                // Resolving can swap the two round: Avanade is absorbed first,
                // so the pair that named it survivor now names its keeper. The
                // shortest name has to win again or "Avanade Spain SL" ends up
                // being the name on the page.
                if ($survivor && $absorbed && ! $survivor->is($absorbed)) {
                    [$survivor, $absorbed] = FindCompanyAliasCandidatesAction::shorterFirst($survivor, $absorbed);
                }

                if ($survivor === null || $absorbed === null || $survivor->is($absorbed)) {
                    $this->line('  <fg=gray>settled</> <fg=gray>' . $candidate['absorbed']->name . ' already belongs to ' . ($survivor?->name ?? 'another company') . '</>');

                    continue;
                }

                $candidate['survivor'] = $survivor;
                $candidate['absorbed'] = $absorbed;

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

    /**
     * Where a company lives now: itself, or whatever swallowed it. Merging
     * repoints the aliases of the company it removes, so one lookup is enough.
     */
    private function current(Company $company): ?Company
    {
        return Company::query()->find($company->id)
            ?? CompanyAlias::companyFor($company->name_normalized);
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
