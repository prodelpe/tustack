<?php

namespace App\Actions;

use App\DTOs\CompanyDTO;
use App\Services\Contracts\JobSourceInterface;
use Illuminate\Support\Collection;

class ProcessJobOfferAction
{
    public function __construct(
        private ResolveCompanyAction $resolveCompany,
        private UpsertJobOfferAction $upsertJobOffer,
        private AttachTechnologiesToOfferAction $attachTechnologies,
    ) {}

    public function handle(array $item, JobSourceInterface $source, Collection $technologies): bool
    {
        $dto = $source->normalize($item);

        if (empty($dto->url)) {
            return false;
        }

        $company = $this->resolveCompany->handle(CompanyDTO::fromNormalizedJobOffer($dto));
        $offer = $this->upsertJobOffer->handle($dto, $company);
        $this->attachTechnologies->handle($offer, $dto, $technologies);

        return true;
    }
}
