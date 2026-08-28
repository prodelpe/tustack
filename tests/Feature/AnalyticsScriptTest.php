<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class AnalyticsScriptTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);

        Http::fake();

        $this->app->detectEnvironment(fn () => 'production');
        config(['analytics.enabled' => true]);
    }

    public function test_nothing_is_loaded_when_analytics_are_not_configured(): void
    {
        config(['analytics.umami.script_url' => null, 'analytics.umami.website_id' => null]);

        $this->get('/')->assertOk()->assertDontSee('data-website-id', false);
    }

    public function test_half_a_configuration_loads_nothing(): void
    {
        config([
            'analytics.umami.script_url' => 'https://stats.tustack.es/script.js',
            'analytics.umami.website_id' => null,
        ]);

        $this->get('/')->assertOk()->assertDontSee('data-website-id', false);
    }

    public function test_nothing_is_loaded_outside_production(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        config([
            'analytics.umami.script_url' => 'https://stats.tustack.es/script.js',
            'analytics.umami.website_id' => 'abc-123',
        ]);

        $this->get('/')->assertOk()->assertDontSee('data-website-id', false);
    }

    public function test_nothing_is_loaded_while_switched_off(): void
    {
        config([
            'analytics.enabled'          => false,
            'analytics.umami.script_url' => 'https://stats.tustack.es/script.js',
            'analytics.umami.website_id' => 'abc-123',
        ]);

        $this->get('/')->assertOk()->assertDontSee('data-website-id', false);
    }

    public function test_the_script_is_loaded_once_configured(): void
    {
        config([
            'analytics.umami.script_url' => 'https://stats.tustack.es/script.js',
            'analytics.umami.website_id' => 'abc-123',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('https://stats.tustack.es/script.js', false)
            ->assertSee('data-website-id="abc-123"', false);
    }
}
