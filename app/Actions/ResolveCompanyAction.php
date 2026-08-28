<?php

namespace App\Actions;

use App\DTOs\CompanyDTO;
use App\Models\Company;
use App\Models\CompanyAlias;
use App\Support\CompanyName;

readonly class ResolveCompanyAction
{
    public function __construct(
        private UpdateCompanyAction $updateCompany,
    ) {}

    public function handle(CompanyDTO $dto): ?Company
    {
        if (blank($dto->name)) {
            return null;
        }

        $normalized = CompanyName::normalize($dto->name);

        // A merged name arrives again on every fetch, so without the aliases the
        // company we just removed would be created anew the same night.
        $company = Company::query()->where('name_normalized', $normalized)->first()
            ?? CompanyAlias::companyFor($normalized);

        $created = $company === null;

        $company ??= Company::create(['name' => $dto->name]);

        if ($created || $company->city === null || $company->province_id === null) {
            $this->updateCompany->handle($company, $dto);
        }

        return $company;
    }
}
