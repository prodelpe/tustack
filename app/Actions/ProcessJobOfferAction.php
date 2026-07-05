<?php

namespace App\Actions;

use App\DTOs\CompanyDTO;
use App\Services\Contracts\JobSourceInterface;
use Illuminate\Support\Collection;

readonly class ProcessJobOfferAction
{
    private const BLACKLISTED_COMPANIES = [
        'jobleads',
        'jobtome',
        'domestiko.com',
    ];

    public function __construct(
        private DetectTechnologiesAction $detectTechnologies,
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

        if ($this->isBlacklisted($dto->company)) {
            return false;
        }

        $matched = $this->detectTechnologies->handle($dto, $technologies);

        if ($matched->isEmpty()) {
            return false;
        }

        $company = $this->resolveCompany->handle(CompanyDTO::fromNormalizedJobOffer($dto));
        $offer = $this->upsertJobOffer->handle($dto, $company);
        $this->attachTechnologies->handle($offer, $dto, $technologies);

        return true;
    }

    private function isBlacklisted(?string $company): bool
    {
        if (blank($company)) {
            return false;
        }

        $normalized = strtolower(trim($company));

        foreach (self::BLACKLISTED_COMPANIES as $blacklisted) {
            if (str_contains($normalized, $blacklisted)) {
                return true;
            }
        }

        return false;
    }
}
