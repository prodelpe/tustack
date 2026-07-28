<?php

namespace App\Actions;

use App\DTOs\CompanyDTO;
use App\Models\Company;

readonly class UpdateCompanyAction
{
    public function __construct(
        private ResolveProvinceAction $resolveProvince,
    ) {}

    public function handle(Company $company, CompanyDTO $dto): Company
    {
        $province = $this->resolveProvince->handle($dto->province, $dto->city, $dto->location);

        $company->update([
            'location'    => $dto->location ?: $company->location,
            'country'     => $dto->country ?: $company->country,
            'city'        => $dto->city ?: $company->city,
            'province_id' => $province ?? $company->province_id,
            'latitude'    => $dto->latitude ?? $company->latitude,
            'longitude'   => $dto->longitude ?? $company->longitude,
        ]);

        return $company;
    }
}
