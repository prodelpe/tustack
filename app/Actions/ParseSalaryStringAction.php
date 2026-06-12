<?php

namespace App\Actions;

class ParseSalaryStringAction
{
    public function handle(?string $salary): array
    {
        if (blank($salary)) {
            return ['min' => null, 'max' => null];
        }

        $isMonthly = stripos($salary, 'month') !== false || stripos($salary, 'mes') !== false;

        $numbers = $this->extractNumbers($salary);

        if (empty($numbers)) {
            return ['min' => null, 'max' => null];
        }

        $min = $numbers[0];
        $max = $numbers[1] ?? $numbers[0];

        if ($isMonthly) {
            $min = $min * 12;
            $max = $max * 12;
        }

        return ['min' => $min, 'max' => $max];
    }

    private function extractNumbers(string $salary): array
    {
        // Normalise both European (30.000) and US (30,000) thousand separators
        // before casting to int, so we don't confuse them with decimal points.
        $normalised = preg_replace_callback(
            '/\d{1,3}([.,])\d{3}(?!\d)/',
            fn ($m) => str_replace($m[1], '', $m[0]),
            $salary
        );

        preg_match_all('/\d+/', $normalised, $matches);

        return array_map('intval', $matches[0]);
    }
}
