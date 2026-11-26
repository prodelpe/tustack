<?php

namespace Tests\Feature;

use App\Jobs\SendSearchAlertJob;
use App\Mail\SearchAlertMail;
use App\Models\Company;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SendSearchAlertJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_search_stays_pending_when_every_delivery_failed(): void
    {
        $search = $this->searchWithNewCompany(alertsEnabled: true);

        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp down'));

        (new SendSearchAlertJob($search))->handle();

        $this->assertNull($search->fresh()->last_notified_at);
    }

    public function test_a_search_is_marked_as_notified_when_the_mail_was_sent(): void
    {
        $search = $this->searchWithNewCompany(alertsEnabled: true);

        Mail::fake();

        (new SendSearchAlertJob($search))->handle();

        Mail::assertSent(SearchAlertMail::class);
        $this->assertNotNull($search->fresh()->last_notified_at);
    }

    public function test_a_search_is_marked_as_notified_when_no_channel_is_enabled(): void
    {
        $search = $this->searchWithNewCompany(alertsEnabled: false);

        Mail::fake();

        (new SendSearchAlertJob($search))->handle();

        Mail::assertNothingSent();
        $this->assertNotNull($search->fresh()->last_notified_at);
    }

    private function searchWithNewCompany(bool $alertsEnabled): SavedSearch
    {
        $user = User::factory()->create(['alerts_enabled' => $alertsEnabled]);

        $search = SavedSearch::create([
            'user_id' => $user->id,
            'filters' => [],
            'filters_hash' => 'hash',
        ]);

        $search->forceFill(['created_at' => now()->subDay()])->save();

        Company::create(['name' => 'Acme']);

        return $search;
    }
}
