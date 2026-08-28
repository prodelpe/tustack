<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_sitemap_lists_every_catalogued_company_in_every_locale(): void
    {
        $company = Company::create(['name' => 'Acme']);

        $offer = JobOffer::create([
            'company_id' => $company->id,
            'title'      => 'Java Developer',
            'url'        => 'https://example.test/java',
            'source'     => 'jooble',
        ]);

        $offer->technologies()->attach(Technology::create(['name' => 'Java', 'slug' => 'java']));

        $response = $this->get('/sitemap.xml');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml, 'the sitemap is not valid xml');

        $xml->registerXPathNamespace('sitemap', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $locations = array_map('strval', $xml->xpath('//sitemap:loc'));

        $this->assertContains(url('/es/empresas/acme'), $locations);
        $this->assertContains(url('/ca/empreses/acme'), $locations);
        $this->assertContains(url('/en/companies/acme'), $locations);
    }

    public function test_humans_is_readable_but_not_indexable(): void
    {
        $this->get('/humans.txt')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Pau Rodellino');
    }

    public function test_robots_blocks_everything_while_the_site_is_closed(): void
    {
        config(['app.noindex' => true]);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /')
            ->assertDontSee('Sitemap:');
    }

    public function test_robots_points_to_the_sitemap_once_the_site_is_open(): void
    {
        config(['app.noindex' => false, 'app.available' => true]);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: ' . route('sitemap'))
            ->assertSee('Disallow: /admin');
    }
}
