<?php

namespace Tests\Feature;

use App\Services\AdzunaService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class AdzunaRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
        Http::preventStrayRequests();
    }

    /** What the first night in production looked like: 26 queries lost to a busy Adzuna. */
    public function test_a_busy_adzuna_is_asked_again_until_it_answers(): void
    {
        Http::fake(['api.adzuna.com/*' => Http::sequence()
            ->push('', 503)
            ->push('', 503)
            ->push(['count' => 1, 'results' => [['title' => 'Laravel Developer']]], 200),
        ]);

        $offers = app(AdzunaService::class)->fetchAll('Laravel');

        $this->assertCount(1, $offers);
        Http::assertSentCount(3);
        Sleep::assertSleptTimes(2);
    }

    public function test_too_many_requests_is_retried_too(): void
    {
        Http::fake(['api.adzuna.com/*' => Http::sequence()
            ->push('', 429)
            ->push(['count' => 0, 'results' => []], 200),
        ]);

        app(AdzunaService::class)->fetchAll('Laravel');

        Http::assertSentCount(2);
    }

    public function test_the_wait_adzuna_asks_for_is_respected(): void
    {
        Http::fake(['api.adzuna.com/*' => Http::sequence()
            ->push('', 429, ['Retry-After' => '7'])
            ->push(['count' => 0, 'results' => []], 200),
        ]);

        app(AdzunaService::class)->fetchAll('Laravel');

        Sleep::assertSequence([Sleep::for(7000)->milliseconds()]);
    }

    public function test_an_absurd_wait_is_capped_so_the_job_does_not_time_out(): void
    {
        Http::fake(['api.adzuna.com/*' => Http::sequence()
            ->push('', 503, ['Retry-After' => '600'])
            ->push(['count' => 0, 'results' => []], 200),
        ]);

        app(AdzunaService::class)->fetchAll('Laravel');

        Sleep::assertSequence([Sleep::for(30000)->milliseconds()]);
    }

    public function test_a_bad_key_fails_at_once_instead_of_waiting(): void
    {
        Http::fake(['api.adzuna.com/*' => Http::response('', 401)]);

        try {
            app(AdzunaService::class)->fetchAll('Laravel');
            $this->fail('A 401 must not be swallowed');
        } catch (RequestException $e) {
            $this->assertSame(401, $e->response->status());
        }

        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_it_gives_up_after_the_last_attempt(): void
    {
        Http::fake(['api.adzuna.com/*' => Http::response('', 503)]);

        $this->expectException(RequestException::class);

        try {
            app(AdzunaService::class)->fetchAll('Laravel');
        } finally {
            Http::assertSentCount(4);
        }
    }
}
