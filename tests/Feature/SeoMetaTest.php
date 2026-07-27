<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class SeoMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);
    }

    public function test_the_home_page_carries_its_own_title_and_description(): void
    {
        Http::fake();

        $response = $this->get('/');

        $response->assertSee('<title>' . __('seo.home_title') . ' · TuStack</title>', escape: false);
        $response->assertSee(__('seo.home_description'), escape: false);
        $response->assertSee('og:image', escape: false);
    }

    public function test_a_company_page_carries_its_own_title(): void
    {
        $company = Company::create(['name' => 'Acme', 'city' => 'Barcelona']);

        $this->get(route('companies.show', $company))
            ->assertSee('<title>Acme · TuStack</title>', escape: false);
    }

    public function test_the_noindex_tag_follows_the_config_flag(): void
    {
        Http::fake();

        config(['app.noindex' => true]);
        $this->get('/')->assertSee('noindex, nofollow', escape: false);

        config(['app.noindex' => false]);
        $this->get('/')->assertDontSee('noindex, nofollow', escape: false);
    }
}
