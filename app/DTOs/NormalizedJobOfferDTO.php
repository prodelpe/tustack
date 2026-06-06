<?php

namespace App\DTOs;

class NormalizedJobOfferDTO
{
    public function __construct(
        public readonly string $url,
        public readonly string $source,
        public readonly ?string $title,
        public readonly ?string $company,
        public readonly ?string $location,
        public readonly ?string $city,
        public readonly ?string $province,
        public readonly ?string $country,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
        public readonly ?int $salaryMin,
        public readonly ?int $salaryMax,
        public readonly ?bool $salaryIsPredicted,
        public readonly ?string $description,
        public readonly ?string $publishedAt,
    ) {}
}
