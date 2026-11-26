<?php

namespace Tests\Feature;

use App\Actions\EnrichCompanyWithGeminiAction;
use App\Models\Company;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\GeminiUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CompanyEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.enabled' => true,
            'services.gemini.api_key' => 'test-key',
        ]);
        Notification::fake();
    }

    public function test_a_quota_failure_keeps_the_company_pending_and_alerts_the_admins(): void
    {
        $admin   = User::factory()->create(['is_admin' => true]);
        $company = Company::create(['name' => 'Acme']);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429)]);

        app(EnrichCompanyWithGeminiAction::class)->handle($company);

        $this->assertFalse((bool) $company->fresh()->gemini_enriched);
        Notification::assertSentTo($admin, GeminiUnavailable::class);
    }

    public function test_only_one_alert_is_sent_per_outage(): void
    {
        User::factory()->create(['is_admin' => true]);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429)]);

        foreach (['Acme', 'Globex', 'Initech'] as $name) {
            app(EnrichCompanyWithGeminiAction::class)->handle(Company::create(['name' => $name]));
        }

        Notification::assertSentTimes(GeminiUnavailable::class, 1);
    }

    public function test_the_gemini_alert_goes_to_telegram_before_mail(): void
    {
        $this->assertSame(
            [TelegramChannel::class, 'mail'],
            (new GeminiUnavailable('quota'))->via(new User)
        );
    }

    public function test_a_rejected_request_marks_the_company_as_processed(): void
    {
        $company = Company::create(['name' => 'Acme']);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'bad request'], 400)]);

        app(EnrichCompanyWithGeminiAction::class)->handle($company);

        $this->assertTrue((bool) $company->fresh()->gemini_enriched);
        Notification::assertNothingSent();
    }

    public function test_the_api_key_travels_in_a_header_and_never_in_the_url(): void
    {
        $company = Company::create(['name' => 'Acme']);

        Http::fake(['generativelanguage.googleapis.com/*' => Http::response(['error' => 'bad request'], 400)]);

        app(EnrichCompanyWithGeminiAction::class)->handle($company);

        Http::assertSent(fn ($request) => $request->hasHeader('x-goog-api-key', 'test-key')
            && ! str_contains($request->url(), 'test-key'));
    }

    protected function tearDown(): void
    {
        Cache::forget('gemini-unavailable-alert');

        parent::tearDown();
    }

    /** A website of 400 characters did not fit its column and the company was paid for again on every run. */
    public function test_values_that_do_not_look_right_are_dropped_and_the_rest_is_kept(): void
    {
        $company = Company::create(['name' => 'Acme']);

        $this->geminiAnswers([
            'description' => 'Acme builds anvils.',
            'sector'      => 'not-a-sector',
            'employees'   => '5000+}^{latitude:40.4705,',
            'website'     => 'https://acme.example/' . str_repeat('a', 400),
            'latitude'    => 4047.05,
            'longitude'   => -3.7,
        ]);

        $this->assertTrue(app(EnrichCompanyWithGeminiAction::class)->handle($company));

        $company->refresh();

        $this->assertTrue((bool) $company->gemini_enriched);
        $this->assertSame('Acme builds anvils.', $company->description['en']);
        $this->assertNull($company->sector);
        $this->assertNull($company->employees);
        $this->assertNull($company->website);
        $this->assertNull($company->latitude);
        $this->assertEqualsWithDelta(-3.7, (float) $company->longitude, 0.0001);
    }

    public function test_clean_values_are_stored_as_they_come(): void
    {
        $company = Company::create(['name' => 'Acme']);

        $this->geminiAnswers([
            'description' => 'Acme builds anvils.',
            'sector'      => array_key_first(config('sectors')),
            'employees'   => '51-200',
            'website'     => 'https://acme.example',
        ]);

        app(EnrichCompanyWithGeminiAction::class)->handle($company);

        $company->refresh();

        $this->assertSame(array_key_first(config('sectors')), $company->sector);
        $this->assertSame('51-200', $company->employees);
        $this->assertSame('https://acme.example', $company->website);
        $this->assertSame('translated', $company->description['es']);
    }

    public function test_a_runaway_description_is_never_sent_to_be_translated(): void
    {
        $company = Company::create(['name' => 'Acme']);

        $this->geminiAnswers(['description' => str_repeat('Acme builds anvils. ', 200), 'employees' => '11-50']);

        app(EnrichCompanyWithGeminiAction::class)->handle($company);

        $this->assertNull($company->fresh()->description);
        $this->assertSame('11-50', $company->fresh()->employees);
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'translation.googleapis.com'));
    }

    private function geminiAnswers(array $answer): void
    {
        config(['services.google_translate.api_key' => 'translate-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($answer)]]]]],
            ]),
            'translation.googleapis.com/*' => Http::response([
                'data' => ['translations' => [['translatedText' => 'translated']]],
            ]),
        ]);
    }
}
