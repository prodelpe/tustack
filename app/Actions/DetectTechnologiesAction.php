<?php

namespace App\Actions;

use App\DTOs\NormalizedJobOfferDTO;
use App\Models\Technology;
use Illuminate\Support\Collection;

class DetectTechnologiesAction
{
    public function handle(NormalizedJobOfferDTO $dto, Collection $technologies): Collection
    {
        $text = strtolower(($dto->title ?? '') . ' ' . ($dto->description ?? ''));

        return $technologies->filter(function (Technology $tech) use ($text) {
            foreach ($this->terms($tech) as $term) {
                if ($this->matches($text, $term)) {
                    return true;
                }
            }
            return false;
        });
    }

    private function terms(Technology $tech): array
    {
        return array_filter(array_merge([$tech->name], $tech->aliases ?? []));
    }

    private function matches(string $text, string $term): bool
    {
        $quoted   = preg_quote(strtolower($term), '/');
        $leading  = ctype_alpha($term[0]) ? '\b' : '';
        $trailing = ctype_alnum($term[-1]) ? '\b' : '';

        return (bool) preg_match('/' . $leading . $quoted . $trailing . '/i', $text);
    }
}
