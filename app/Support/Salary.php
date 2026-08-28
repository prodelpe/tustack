<?php

namespace App\Support;

class Salary
{
    private const MONTHLY = '/\bmes\b|\bmensual(es)?\b|\bmonth(ly)?\b/iu';

    private const PER_HOUR_OR_DAY = '/\bhoras?\b|\bhours?\b|\bhourly\b|\bd[íi]as?\b|\bdays?\b|\bdaily\b|\bdiario\b/iu';

    public static function parse(?string $salary): array
    {
        if (blank($salary) || preg_match(self::PER_HOUR_OR_DAY, $salary)) {
            return self::none();
        }

        $numbers = self::numbers($salary);

        if ($numbers === []) {
            return self::none();
        }

        $months = preg_match(self::MONTHLY, $salary) ? 12 : 1;

        return [
            'min' => $numbers[0] * $months,
            'max' => ($numbers[1] ?? $numbers[0]) * $months,
        ];
    }

    private static function numbers(string $salary): array
    {
        $salary = self::withoutThousandsSeparators($salary);
        $salary = self::withoutDecimals($salary);

        preg_match_all('/\d+/', $salary, $matches);

        return array_map('intval', $matches[0]);
    }

    private static function withoutThousandsSeparators(string $salary): string
    {
        return preg_replace('/(\d)[.,](\d{3})(?!\d)/', '$1$2', $salary);
    }

    private static function withoutDecimals(string $salary): string
    {
        return preg_replace('/(\d)[.,]\d{1,2}(?!\d)/', '$1', $salary);
    }

    private static function none(): array
    {
        return ['min' => null, 'max' => null];
    }
}
