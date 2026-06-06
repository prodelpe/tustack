<?php

namespace App\DTOs;

class CompanyDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $location,
        public readonly ?string $city,
        public readonly ?string $province,
        public readonly ?string $country,
        public readonly ?float $latitude,
        public readonly ?float $longitude,
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
