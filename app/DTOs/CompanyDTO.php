<?php

namespace App\DTOs;

readonly class CompanyDTO
{
    public function __construct(
        public string $name,
        public ?string $location,
        public ?string $city,
        public ?string $province,
        public ?string $country,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    public static function fromNormalizedJobOffer(NormalizedJobOfferDTO $dto): self
    {
        return new self(
            name: $dto->company ?? '',
            location: $dto->location,
            city: $dto->city,
            province: $dto->province,
            country: $dto->country,
            latitude: $dto->latitude,
            longitude: $dto->longitude,
        );
    }
}
