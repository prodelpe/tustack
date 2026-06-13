<?php

namespace App\Actions;

use App\DTOs\NormalizedJobOfferDTO;
use App\Models\JobOffer;
use Illuminate\Support\Collection;

readonly class AttachTechnologiesToOfferAction
{
    public function __construct(
        private DetectTechnologiesAction $detectTechnologies,
        private DetectTechnologiesWithGeminiAction $detectWithGemini,
    ) {}

    public function handle(JobOffer $offer, NormalizedJobOfferDTO $dto, Collection $technologies): void
    {
        if (! $offer->wasRecentlyCreated) {
            return;
        }

        $matched = $this->detectTechnologies->handle($dto, $technologies);

        if ($matched->isEmpty() && ! $offer->gemini_processed) {
            $matched = $this->detectWithGemini->handle($dto, $technologies);
            $offer->update(['gemini_processed' => true]);
        }

        if ($matched->isNotEmpty()) {
            $offer->technologies()->attach($matched->pluck('id'));
        }
    }
}
