<?php

namespace App\Actions;

use App\DTOs\NormalizedJobOfferDTO;
use App\Models\JobOffer;
use Illuminate\Support\Collection;

class AttachTechnologiesToOfferAction
{
    public function __construct(
        private DetectTechnologiesAction $detectTechnologies,
    ) {}

    public function handle(JobOffer $offer, NormalizedJobOfferDTO $dto, Collection $technologies): void
    {
        if (! $offer->wasRecentlyCreated) {
            return;
        }

        $matched = $this->detectTechnologies->handle($dto, $technologies);

        if ($matched->isNotEmpty()) {
            $offer->technologies()->attach($matched->pluck('id'));
        }
    }
}
