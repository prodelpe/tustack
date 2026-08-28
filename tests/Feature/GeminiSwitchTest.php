<?php

namespace Tests\Feature;

use App\Actions\AskGeminiAboutCompanyNamesAction;
use App\Actions\EnrichCompanyWithGeminiAction;
use App\Models\Company;
use App\Support\Gemini;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake();

        config(['services.gemini.api_key' => 'a-key']);
    }

    public function test_the_switch_is_off_unless_someone_turns_it_on(): void
    {
        config(['services.gemini.enabled' => false]);

        $this->assertFalse(Gemini::isEnabled());
    }

    public function test_a_key_without_the_switch_is_still_off(): void
    {
        config(['services.gemini.enabled' => false, 'services.gemini.api_key' => 'a-key']);

        $this->assertFalse(Gemini::isEnabled());
    }

    public function test_the_switch_without_a_key_is_off_too(): void
    {
        config(['services.gemini.enabled' => true, 'services.gemini.api_key' => null]);

        $this->assertFalse(Gemini::isEnabled());
    }

    public function test_nothing_reaches_the_api_while_it_is_off(): void
    {
        config(['services.gemini.enabled' => false]);

        $verdicts = app(AskGeminiAboutCompanyNamesAction::class)
            ->judgePairs([['Otis', 'Otis Elevator Company']]);

        $enriched = app(EnrichCompanyWithGeminiAction::class)
            ->handle(Company::create(['name' => 'Acme']));

        $this->assertNull($verdicts);
        $this->assertFalse($enriched);

        Http::assertNothingSent();
    }

    public function test_a_company_refused_by_the_switch_stays_pending(): void
    {
        config(['services.gemini.enabled' => false]);

        $company = Company::create(['name' => 'Acme']);

        app(EnrichCompanyWithGeminiAction::class)->handle($company);

        $this->assertFalse((bool) $company->fresh()->gemini_enriched);
    }

    public function test_the_enrich_command_refuses_to_run_while_it_is_off(): void
    {
        config(['services.gemini.enabled' => false]);

        Company::create(['name' => 'Acme']);

        $this->artisan('companies:enrich --min-technologies=0')
            ->expectsOutputToContain('Gemini is switched off')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }

    public function test_the_estimate_still_works_while_it_is_off(): void
    {
        config(['services.gemini.enabled' => false]);

        Company::create(['name' => 'Acme']);

        $this->artisan('companies:enrich --estimate --min-technologies=0')
            ->assertExitCode(0);
    }

    public function test_the_alias_command_refuses_even_in_dry_run(): void
    {
        config(['services.gemini.enabled' => false]);

        $this->artisan('companies:find-aliases --dry-run')
            ->expectsOutputToContain('Gemini is switched off')
            ->assertExitCode(1);

        Http::assertNothingSent();
    }
}
