<?php

namespace Tests\Unit;

use App\Support\Salary;
use PHPUnit\Framework\TestCase;

class SalaryTest extends TestCase
{
    public function test_a_single_amount_with_decimals_is_not_read_as_a_range(): void
    {
        $this->assertSame(['min' => 22515, 'max' => 22515], Salary::parse('22.515,89 €'));
        $this->assertSame(['min' => 35265, 'max' => 35265], Salary::parse('35.265,66 EUR'));
    }

    public function test_a_range_is_kept_as_a_range(): void
    {
        $this->assertSame(['min' => 30000, 'max' => 40000], Salary::parse('30.000 - 40.000 €'));
    }

    public function test_a_range_with_decimals_keeps_both_ends(): void
    {
        $this->assertSame(['min' => 22515, 'max' => 30000], Salary::parse('22.515,89 - 30.000,00 €'));
    }

    public function test_the_us_thousands_separator_still_works(): void
    {
        $this->assertSame(['min' => 45000, 'max' => 45000], Salary::parse('$45,000 per year'));
    }

    public function test_a_monthly_amount_becomes_a_yearly_one(): void
    {
        $this->assertSame(['min' => 18000, 'max' => 18000], Salary::parse('1.500 € al mes'));
        $this->assertSame(['min' => 18000, 'max' => 18000], Salary::parse('1.500 € mensuales'));
        $this->assertSame(['min' => 24000, 'max' => 24000], Salary::parse('2000 € brutos/mes'));
        $this->assertSame(['min' => 36000, 'max' => 36000], Salary::parse('3000 EUR monthly'));
    }

    public function test_a_yearly_amount_is_left_alone(): void
    {
        $this->assertSame(['min' => 30000, 'max' => 30000], Salary::parse('30.000 € anual'));
        $this->assertSame(['min' => 40000, 'max' => 40000], Salary::parse('40.000 € gross per year'));
    }

    public function test_a_word_that_merely_contains_mes_is_not_a_monthly_amount(): void
    {
        $this->assertSame(['min' => 30000, 'max' => 30000], Salary::parse('30.000 € por trimestre'));
    }

    public function test_rates_per_hour_or_per_day_are_discarded(): void
    {
        $this->assertSame(['min' => null, 'max' => null], Salary::parse('55 - 77 € per hour'));
        $this->assertSame(['min' => null, 'max' => null], Salary::parse('65 €/hora'));
        $this->assertSame(['min' => null, 'max' => null], Salary::parse('420 - 600 € al día'));
        $this->assertSame(['min' => null, 'max' => null], Salary::parse('500 EUR daily'));
    }

    public function test_nothing_usable_gives_nothing(): void
    {
        $this->assertSame(['min' => null, 'max' => null], Salary::parse(null));
        $this->assertSame(['min' => null, 'max' => null], Salary::parse('a convenir'));
    }
}
