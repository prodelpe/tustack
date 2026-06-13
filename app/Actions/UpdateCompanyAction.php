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
        $company->update([
            'location'    => $dto->location,
            'country'     => $dto->country,
            'city'        => $dto->city,
            'province_id' => $this->resolveProvince->handle($dto->province),
            'latitude'    => $dto->latitude,
            'longitude'   => $dto->longitude,
        ]);

        return $company;
    }
}
