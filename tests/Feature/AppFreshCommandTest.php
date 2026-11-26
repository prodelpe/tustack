<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppFreshCommandTest extends TestCase
{
    public function test_it_refuses_to_run_in_production_without_asking_anything(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('app:fresh')
            ->expectsOutputToContain('disabled in production')
            ->assertFailed();
    }
}
