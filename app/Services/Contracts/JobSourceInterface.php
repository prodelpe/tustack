<?php

namespace App\Services\Contracts;

use App\DTOs\NormalizedJobOfferDTO;

interface JobSourceInterface
{
    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null, ?int $sinceDays = null): array;

    public function normalize(array $raw): NormalizedJobOfferDTO;
}
