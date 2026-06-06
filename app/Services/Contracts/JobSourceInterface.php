<?php

namespace App\Services\Contracts;

interface JobSourceInterface
{
    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null): array;

    public function normalize(array $raw): array;
}
