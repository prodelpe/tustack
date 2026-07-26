<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_is_displayed(): void
    {
        // Routes boot before any request exists, so locale prefixes are empty here
        // and the redirect would bounce to a URL that is not registered.
        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);

        Http::fake();

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('TuStack', escape: false);
    }

    public function test_the_root_url_redirects_to_a_localized_url(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
    }
}
