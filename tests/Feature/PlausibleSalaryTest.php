<?php

namespace Tests\Feature;

use App\Support\PlausibleSalary;
use Tests\TestCase;

class PlausibleSalaryTest extends TestCase
{
    public function test_a_salary_inside_the_band_is_kept(): void
    {
        $this->assertSame(['min' => 30000, 'max' => 45000], PlausibleSalary::filter(30000, 45000));
    }

    public function test_only_one_end_is_enough(): void
    {
        $this->assertSame(['min' => 30000, 'max' => null], PlausibleSalary::filter(30000, null));
        $this->assertSame(['min' => null, 'max' => 45000], PlausibleSalary::filter(null, 45000));
    }

    public function test_an_amount_below_the_floor_drops_the_whole_salary(): void
    {
        $this->assertSame(['min' => null, 'max' => null], PlausibleSalary::filter(55, 77));
        $this->assertSame(['min' => null, 'max' => null], PlausibleSalary::filter(1500, 40000));
    }

    public function test_an_amount_above_the_ceiling_drops_the_whole_salary(): void
    {
        $this->assertSame(['min' => null, 'max' => null], PlausibleSalary::filter(75000, 700000));
    }

    public function test_a_minimum_above_the_maximum_drops_the_whole_salary(): void
    {
        $this->assertSame(['min' => null, 'max' => null], PlausibleSalary::filter(22515, 89));
    }

    public function test_the_band_comes_from_the_configuration(): void
    {
        config(['jobs.salary_floor' => 20000]);

        $this->assertSame(['min' => null, 'max' => null], PlausibleSalary::filter(15000, 30000));
    }
}
