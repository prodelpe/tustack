<?php

namespace App\Actions;

use App\Models\JobOffer;
use Illuminate\Support\Collection;

readonly class AttachTechnologiesToOfferAction
{
    public function handle(JobOffer $offer, Collection $matched): void
    {
        if (! $offer->wasRecentlyCreated) {
            return;
        }

        if ($matched->isNotEmpty()) {
            $offer->technologies()->attach($matched->pluck('id'));
        }
    }
}
