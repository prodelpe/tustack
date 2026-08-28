<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\CompanyAlias;
use App\Support\CompanyName;
use Illuminate\Support\Collection;

/**
 * Pairs of company names worth asking about. This runs on our own data and
 * costs nothing: the rules only narrow two thousand names down to a few
 * hundred pairs, they never decide anything.
 */
readonly class FindCompanyAliasCandidatesAction
{
    /**
     * Words that describe a company without naming one. A few of these are
     * companies in the catalogue on their own, so without this list "Consulting,
     * Inc." would be offered as the true name of Capitole Consulting.
     */
    private const GENERIC = [
        'consulting', 'consultoria', 'consultores', 'consultants', 'international',
        'group', 'grupo', 'holding', 'holdings', 'solutions', 'soluciones',
        'technologies', 'technology', 'tecnologia', 'tecnologias', 'systems',
        'sistemas', 'services', 'servicios', 'digital', 'global', 'partner',
        'partners', 'company', 'corporation', 'corp', 'iberia', 'spain', 'espana',
        'europe', 'europa', 'north', 'south', 'east', 'west', 'tech', 'software',
        'engineering', 'ingenieria', 'talent', 'staffing', 'recruitment',
        'seleccion', 'empresa', 'team', 'people', 'work', 'jobs', 'career',
        'careers', 'agency', 'studio', 'lab', 'labs',
    ];

    /**
     * @return Collection<int, array{absorbed: Company, survivor: Company, rule: string}>
     */
    public function handle(?int $limit = null): Collection
    {
        $companies = Company::query()
            ->select(['id', 'name', 'name_normalized'])
            ->orderBy('id')
            ->get();

        $decided = CompanyAlias::query()->pluck('pair_key')->flip();

        $candidates = $this->containedNames($companies)
            ->merge($this->nearlyIdenticalNames($companies))
            ->reject(function (array $candidate) use ($decided) {
                $key = CompanyAlias::pairKey($candidate['absorbed']->name, $candidate['survivor']->name);

                return $decided->has($key);
            })
            ->unique(function (array $candidate) {
                return CompanyAlias::pairKey($candidate['absorbed']->name, $candidate['survivor']->name);
            })
            ->values();

        return $limit ? $candidates->take($limit)->values() : $candidates;
    }

    /**
     * One name reading inside another: Otis inside Otis Elevator Company. Words
     * are compared whole, so Sap does not read inside Sapiens.
     *
     * @param  Collection<int, Company>  $companies
     * @return Collection<int, array{absorbed: Company, survivor: Company, rule: string}>
     */
    private function containedNames(Collection $companies): Collection
    {
        $byName = $companies->keyBy('name_normalized');
        $found  = collect();

        foreach ($companies as $company) {
            $words = CompanyName::comparableWords($company->name);

            if (count($words) < 2) {
                continue;
            }

            foreach ($this->runsOfWords($words) as $run) {
                $shorter = $byName->get($run);

                if ($shorter && $shorter->id !== $company->id && $this->namesSomeone($shorter->name)) {
                    $found->push([
                        'absorbed' => $company,
                        'survivor' => $shorter,
                        'rule'     => 'contained',
                    ]);
                }
            }
        }

        return $found;
    }

    /**
     * True when a name says who the company is, and not only what it does.
     * A name of a single very short word is excluded too: it is a fragment.
     */
    private function namesSomeone(string $name): bool
    {
        $words = CompanyName::comparableWords($name);

        if ($words === [] || mb_strlen(implode('', $words)) < 3) {
            return false;
        }

        foreach ($words as $word) {
            if (! in_array($word, self::GENERIC, strict: true)) {
                return true;
            }
        }

        return false;
    }

    /** Every run of consecutive words except the whole name. */
    private function runsOfWords(array $words): array
    {
        $runs  = [];
        $total = count($words);

        for ($start = 0; $start < $total; $start++) {
            for ($length = 1; $length <= $total - $start; $length++) {
                if ($length === $total) {
                    continue;
                }

                $runs[] = implode('', array_slice($words, $start, $length));
            }
        }

        return $runs;
    }

    /**
     * Names a couple of letters apart, which on a long name is a typo or a lost
     * space: "Thewhiteam" and "The White Team". Short names are left out because
     * two edits are nothing there, where Adyen meets Aderen and Viseo meets
     * Cisco: two hundred questions to reunite nobody.
     *
     * @param  Collection<int, Company>  $companies
     * @return Collection<int, array{absorbed: Company, survivor: Company, rule: string}>
     */
    private function nearlyIdenticalNames(Collection $companies): Collection
    {
        $distance  = config('companies.alias_max_edit_distance');
        $minLength = config('companies.alias_minimum_length_for_edit_distance');

        $names = $companies
            ->filter(fn (Company $company) => mb_strlen((string) $company->name_normalized) >= $minLength)
            ->values();

        $found = collect();

        foreach ($names as $index => $company) {
            foreach ($names->slice($index + 1) as $other) {
                $lengths = abs(strlen($company->name_normalized) - strlen($other->name_normalized));

                if ($lengths > $distance) {
                    continue;
                }

                if (levenshtein($company->name_normalized, $other->name_normalized) > $distance) {
                    continue;
                }

                [$survivor, $absorbed] = $this->shorterFirst($company, $other);

                if (! $this->namesSomeone($survivor->name)) {
                    continue;
                }

                $found->push([
                    'absorbed' => $absorbed,
                    'survivor' => $survivor,
                    'rule'     => 'near-identical',
                ]);
            }
        }

        return $found;
    }

    /** The shorter name is the one to show, and the older one settles a tie. */
    private function shorterFirst(Company $first, Company $second): array
    {
        $byLength = mb_strlen($first->name) <=> mb_strlen($second->name);

        if ($byLength === 0) {
            return $first->id <= $second->id ? [$first, $second] : [$second, $first];
        }

        return $byLength < 0 ? [$first, $second] : [$second, $first];
    }
}
