<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class CompanySlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_slug_is_generated_from_the_name(): void
    {
        $company = Company::create(['name' => 'Alovia Consulting, S.L']);

        $this->assertSame('alovia-consulting-sl', $company->slug);
    }

    public function test_companies_sharing_a_name_get_distinct_slugs(): void
    {
        $first  = Company::create(['name' => 'Acme']);
        $second = Company::create(['name' => 'Acme']);

        $this->assertSame('acme', $first->slug);
        $this->assertSame('acme-2', $second->slug);
    }

    public function test_a_renamed_company_keeps_its_slug(): void
    {
        $company = Company::create(['name' => 'Acme']);

        $company->update(['name' => 'Acme International']);

        $this->assertSame('acme', $company->fresh()->slug);
    }

    public function test_the_company_page_is_served_from_the_slug(): void
    {
        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);

        $company = Company::create(['name' => 'Acme']);

        $this->get(route('companies.show', $company))
            ->assertOk()
            ->assertSee('Acme');
    }

    public function test_the_old_numeric_url_redirects_to_the_slug(): void
    {
        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);

        $company = Company::create(['name' => 'Acme']);

        $this->get('/companies/' . $company->id)
            ->assertRedirect(route('companies.show', $company))
            ->assertStatus(301);
    }
}
