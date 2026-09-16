<?php

namespace Tests\Feature;

use App\Actions\EnrichCompanyWithGeminiAction;
use App\Jobs\EnrichCompanyJob;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnrichCompanyJobTest extends TestCase
{
    use RefreshDatabase;

    /** What happened on 14 September: 842 companies skipped while the batch reported success. */
    public function test_a_worker_that_sees_gemini_off_fails_instead_of_skipping_the_company(): void
    {
        config(['services.gemini.enabled' => false, 'services.gemini.api_key' => 'a-key']);
        Http::fake();

        $company = Company::create(['name' => 'Acme']);
        $job = (new EnrichCompanyJob($company->id))->withFakeQueueInteractions();

        $job->handle(app(EnrichCompanyWithGeminiAction::class));

        $job->assertFailed();
        $this->assertFalse((bool) $company->fresh()->gemini_enriched);
        Http::assertNothingSent();
    }

    public function test_a_worker_that_sees_gemini_on_enriches_the_company(): void
    {
        config(['services.gemini.enabled' => true, 'services.gemini.api_key' => 'a-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode(['sector' => null])]]]]],
            ]),
        ]);

        $company = Company::create(['name' => 'Acme']);
        $job = (new EnrichCompanyJob($company->id))->withFakeQueueInteractions();

        $job->handle(app(EnrichCompanyWithGeminiAction::class));

        $job->assertNotFailed();
        $this->assertTrue((bool) $company->fresh()->gemini_enriched);
    }
}
