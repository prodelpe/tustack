<?php

namespace App\DTOs;

use App\Enums\JobSource;

readonly class NormalizedJobOfferDTO
{
    public function __construct(
        public string $url,
        public JobSource $source,
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
