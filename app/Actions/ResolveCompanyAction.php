<?php

namespace App\Actions;

use App\DTOs\CompanyDTO;
use App\Models\Company;
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

        $company = Company::query()->firstOrCreate(
            ['name_normalized' => CompanyName::normalize($dto->name)],
            ['name' => $dto->name],
        );

        if ($company->wasRecentlyCreated || $company->city === null || $company->province_id === null) {
            $this->updateCompany->handle($company, $dto);
        }

        return $company;
    }
}
