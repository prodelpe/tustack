<?php

namespace Tests\Feature;

use App\Support\LegalDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);

        Http::fake();
    }

    public function test_every_document_is_served_in_every_language(): void
    {
        foreach (['ca', 'es', 'en'] as $locale) {
            app()->setLocale($locale);

            foreach (LegalDocument::DOCUMENTS as $document) {
                $this->get(route('legal.' . $document))
                    ->assertOk()
                    ->assertSee(__('legal.' . $document . '_heading'), false);
            }
        }
    }

    public function test_every_document_exists_for_every_language(): void
    {
        foreach (['ca', 'es', 'en'] as $locale) {
            foreach (LegalDocument::DOCUMENTS as $document) {
                $this->assertFileExists(resource_path("legal/{$locale}/{$document}.md"));
            }
        }
    }

    public function test_the_markdown_becomes_html_with_its_tables(): void
    {
        app()->setLocale('es');

        $this->get(route('legal.cookies'))
            ->assertOk()
            ->assertSee('<table>', false)
            ->assertSee('tustack-session', false);
    }

    public function test_an_unknown_document_is_not_found(): void
    {
        $this->assertFalse(LegalDocument::isKnown('terms'));
    }

    public function test_the_footer_links_to_the_three_documents(): void
    {
        app()->setLocale('es');

        $response = $this->get(route('home'))->assertOk();

        foreach (LegalDocument::DOCUMENTS as $document) {
            $response->assertSee(route('legal.' . $document), false);
        }
    }

    public function test_the_pages_ask_to_stay_out_of_search_results(): void
    {
        foreach (LegalDocument::DOCUMENTS as $document) {
            $this->get(route('legal.' . $document))
                ->assertOk()
                ->assertSee('name="robots" content="noindex, nofollow"', false);
        }
    }

    public function test_the_pages_are_kept_out_of_the_sitemap(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk()->getContent();

        foreach (['ca', 'es', 'en'] as $locale) {
            foreach (LegalDocument::DOCUMENTS as $document) {
                $slug = trans('routes.' . $document, [], $locale);

                $this->assertStringNotContainsString("/{$locale}/{$slug}", $sitemap);
            }
        }
    }

    public function test_no_home_address_or_tax_id_is_asked_for(): void
    {
        foreach (['ca', 'es', 'en'] as $locale) {
            foreach (LegalDocument::DOCUMENTS as $document) {
                $body = file_get_contents(resource_path("legal/{$locale}/{$document}.md"));

                $this->assertStringNotContainsString('NIF:', $body);
                $this->assertStringNotContainsString('Domicilio:', $body);
                $this->assertStringNotContainsString('Domicili:', $body);
                $this->assertStringNotContainsString('Address:', $body);
            }
        }
    }
}
