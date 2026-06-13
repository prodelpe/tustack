<?php

namespace App\DTOs;

readonly class NormalizedJobOfferDTO
{
    public function __construct(
        public string $url,
        public string $source,
        public ?string $title,
        public ?string $company,
        public ?string $location,
        public ?string $city,
        public ?string $province,
        public ?string $country,
        public ?float $latitude,
        public ?float $longitude,
        public ?int $salaryMin,
        public ?int $salaryMax,
        public ?bool $salaryIsPredicted,
        public ?string $description,
        public ?string $publishedAt,
    ) {}
}
