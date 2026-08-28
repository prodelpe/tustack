<?php

namespace App\Actions;

use App\Models\Company;
use App\Models\CompanyAlias;
use App\Models\JobOffer;
use Illuminate\Support\Facades\DB;

/**
 * Moves everything one company holds onto another and removes it. The decision
 * of whether to merge is not taken here: by the time this runs it is made.
 */
readonly class MergeCompaniesAction
{
    /** Filled on the survivor only where it has nothing, so paid data is never overwritten. */
    private const INHERITED = [
        'description', 'sector', 'employees', 'website',
        'latitude', 'longitude', 'location', 'country', 'city', 'province_id',
    ];

    public function handle(Company $absorbed, Company $survivor): CompanyAlias
    {
        return DB::transaction(function () use ($absorbed, $survivor) {
            $offerIds = $absorbed->jobOffers()->pluck('id')->all();

            JobOffer::query()->whereIn('id', $offerIds)->update(['company_id' => $survivor->id]);

            $survivor->savedByUsers()->syncWithoutDetaching(
                $absorbed->savedByUsers()->pluck('users.id')->all()
            );

            $survivor->update($this->inheritedAttributes($absorbed, $survivor));

            $alias = CompanyAlias::updateOrCreate(
                ['pair_key' => CompanyAlias::pairKey($absorbed->name, $survivor->name)],
                [
                    'name'            => $absorbed->name,
                    'name_normalized' => $absorbed->name_normalized,
                    'company_id'      => $survivor->id,
                    'status'          => CompanyAlias::APPROVED,
                    'moved_offer_ids' => $offerIds,
                ]
            );

            // Names that pointed at the company being removed follow it, so a
            // chain of merges does not leave an alias pointing at nothing.
            CompanyAlias::query()
                ->where('company_id', $absorbed->id)
                ->update(['company_id' => $survivor->id]);

            $absorbed->delete();

            $survivor->searchable();

            return $alias;
        });
    }

    /** Undoes a merge: the absorbed company comes back with the offers it had. */
    public function undo(CompanyAlias $alias): Company
    {
        return DB::transaction(function () use ($alias) {
            $company = Company::create(['name' => $alias->name]);

            JobOffer::query()
                ->whereIn('id', $alias->moved_offer_ids ?? [])
                ->update(['company_id' => $company->id]);

            $alias->update([
                'status'          => CompanyAlias::REJECTED,
                'moved_offer_ids' => null,
                'source'          => 'admin',
            ]);

            $alias->company?->searchable();

            return $company;
        });
    }

    private function inheritedAttributes(Company $absorbed, Company $survivor): array
    {
        $attributes = [];

        foreach (self::INHERITED as $field) {
            if (blank($survivor->{$field}) && filled($absorbed->{$field})) {
                $attributes[$field] = $absorbed->{$field};
            }
        }

        if ($absorbed->gemini_enriched && ! $survivor->gemini_enriched) {
            $attributes['gemini_enriched'] = true;
        }

        return $attributes;
    }
}
