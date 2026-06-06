<?php

namespace App\Actions;

use App\DTOs\NormalizedJobOfferDTO;
use Illuminate\Support\Collection;

class DetectTechnologiesAction
{
    public function handle(NormalizedJobOfferDTO $dto, Collection $technologies): Collection
    {
        $text = strtolower(($dto->title ?? '') . ' ' . ($dto->description ?? ''));

        return $technologies->filter(fn ($tech) => str_contains($text, strtolower($tech->name)));
    }
}
