<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class StructuredDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);
    }

    public function test_the_home_page_describes_the_site_and_its_search(): void
    {
        Http::fake();

        $types = $this->typesIn($this->get('/')->getContent());

        $this->assertContains('WebSite', $types);
        $this->assertContains('Organization', $types);
    }

    public function test_a_company_page_describes_the_organization(): void
    {
        $company = Company::create([
            'name'      => 'Acme',
            'city'      => 'Barcelona',
            'website'   => 'https://acme.test',
            'latitude'  => 41.3874,
            'longitude' => 2.1686,
        ]);

        $content = $this->get(route('companies.show', $company))->getContent();

        $this->assertContains('Organization', $this->typesIn($content));
        $this->assertContains('BreadcrumbList', $this->typesIn($content));
        $this->assertStringContainsString('GeoCoordinates', $content);
    }

    public function test_the_markup_is_valid_json(): void
    {
        $company = Company::create(['name' => 'Acme </script><script>alert(1)</script>']);

        $content = $this->get(route('companies.show', $company))->getContent();

        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $content, $matches);

        $this->assertNotEmpty($matches[1] ?? '');
        $this->assertIsArray(json_decode($matches[1], true), 'the json-ld block is not valid json');
        $this->assertStringNotContainsString('</script><script>alert', $matches[1]);
    }

    /**
     * @return array<int, string>
     */
    private function typesIn(string $html): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        $types = [];

        foreach ($matches[1] ?? [] as $json) {
            $data = json_decode($json, true) ?? [];

            foreach ($data['@graph'] ?? [$data] as $node) {
                $types[] = $node['@type'] ?? null;
            }
        }

        return array_filter($types);
    }
}
