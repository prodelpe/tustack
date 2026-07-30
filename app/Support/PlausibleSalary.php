<?php

namespace App\Support;

class PlausibleSalary
{
    public static function filter(?int $min, ?int $max): array
    {
        if ($min === null && $max === null) {
            return self::none();
        }

        if ($min !== null && $max !== null && $min > $max) {
            return self::none();
        }

        foreach ([$min, $max] as $amount) {
            if ($amount !== null && self::isOutOfBand($amount)) {
                return self::none();
            }
        }

        return ['min' => $min, 'max' => $max];
    }

    private static function isOutOfBand(int $amount): bool
    {
        return $amount < config('jobs.salary_floor')
            || $amount > config('jobs.salary_ceiling');
    }

    private static function none(): array
    {
        return ['min' => null, 'max' => null];
    }
}
