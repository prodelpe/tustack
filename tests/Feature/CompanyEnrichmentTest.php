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

    protected function tearDown(): void
    {
        Cache::forget('gemini-unavailable-alert');

        parent::tearDown();
    }
}
