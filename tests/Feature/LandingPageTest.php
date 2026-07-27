<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Province;
use App\Models\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);
    }

    public function test_a_combination_above_the_threshold_lists_its_companies(): void
    {
        $technology = $this->technology();
        $province   = Province::create(['name' => 'Madrid']);

        $this->companiesUsing($technology, $province, config('seo.minimum_companies'));

        $response = $this->get(route('landing.technology-province', [
            'technology' => $technology,
            'province'   => $province,
        ]));

        $response->assertOk();
        $response->assertSee('Laravel', escape: false);
        $response->assertSee('Madrid', escape: false);
        $response->assertSee('Company 1', escape: false);
    }

    public function test_a_combination_below_the_threshold_does_not_exist(): void
    {
        $technology = $this->technology();
        $province   = Province::create(['name' => 'Madrid']);

        $this->companiesUsing($technology, $province, config('seo.minimum_companies') - 1);

        $this->get(route('landing.technology-province', [
            'technology' => $technology,
            'province'   => $province,
        ]))->assertNotFound();
    }

    public function test_an_unknown_technology_does_not_exist(): void
    {
        Province::create(['name' => 'Madrid']);

        $this->get('/empresas-inventada-madrid')->assertNotFound();
    }

    public function test_the_hub_page_covers_the_whole_country(): void
    {
        $technology = $this->technology();

        $this->companiesUsing($technology, Province::create(['name' => 'Madrid']), config('seo.minimum_companies'));

        $this->get(route('landing.technology', ['technology' => $technology]))
            ->assertOk()
            ->assertSee(__('landing.country'), escape: false);
    }

    public function test_the_sitemap_includes_the_landing_pages(): void
    {
        $technology = $this->technology();
        $province   = Province::create(['name' => 'Madrid']);

        $this->companiesUsing($technology, $province, config('seo.minimum_companies'));

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(url('/es/empresas-laravel-madrid'), escape: false)
            ->assertSee(url('/en/laravel-companies-madrid'), escape: false);
    }

    private function technology(): Technology
    {
        return Technology::create(['name' => 'Laravel', 'slug' => 'laravel']);
    }

    private function companiesUsing(Technology $technology, Province $province, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $company = Company::create([
                'name'        => 'Company ' . $i,
                'province_id' => $province->id,
                'city'        => $province->name,
            ]);

            $offer = JobOffer::create([
                'company_id'   => $company->id,
                'title'        => 'Backend developer',
                'url'          => 'https://example.com/offer/' . $company->id,
                'source'       => 'test',
                'published_at' => now(),
            ]);

            $offer->technologies()->attach($technology->id);
        }
    }
}
