<?php

namespace App\Actions;

use App\DTOs\NormalizedJobOfferDTO;
use App\Models\Company;
use App\Models\JobOffer;

class UpsertJobOfferAction
{
    public function handle(NormalizedJobOfferDTO $dto, ?Company $company): JobOffer
    {
        return JobOffer::firstOrCreate(
            ['url' => $dto->url],
            [
                'company_id'          => $company?->id,
                'title'               => $dto->title,
                'description'         => $dto->description,
                'source'              => $dto->source,
                'published_at'        => $dto->publishedAt,
                'salary_min'          => $dto->salaryMin,
                'salary_max'          => $dto->salaryMax,
                'salary_is_predicted' => $dto->salaryIsPredicted,
            ]
        );
    }
}
