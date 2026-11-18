<?php

namespace Tests\Feature;

use App\Exceptions\JoobleQuotaExceededException;
use App\Services\JoobleService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JoobleQuotaTest extends TestCase
{
    private const QUOTA_EXCEEDED = ['errorCode' => 5, 'statusCode' => 429, 'message' => 'Daily request limit exceeded', 'details' => ''];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_once_the_daily_quota_is_spent_jooble_is_not_asked_again(): void
    {
        Http::fake(['*' => Http::response(self::QUOTA_EXCEEDED, 429)]);

        foreach (['Laravel', 'Vue', 'React'] as $query) {
            try {
                app(JoobleService::class)->fetchAll($query);
                $this->fail('A spent quota must be reported');
            } catch (JoobleQuotaExceededException) {
            }
        }

        Http::assertSentCount(1);
    }

    public function test_the_pages_fetched_before_the_quota_ran_out_are_kept(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['totalCount' => 3, 'jobs' => [['id' => 1], ['id' => 2]]])
            ->push(self::QUOTA_EXCEEDED, 429),
        ]);

        $offers = app(JoobleService::class)->fetchAll('Laravel');

        $this->assertCount(2, $offers);
        $this->assertTrue(JoobleService::quotaIsExhausted());
    }

    public function test_the_quota_is_asked_again_the_next_day(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(self::QUOTA_EXCEEDED, 429)
            ->push(['totalCount' => 0, 'jobs' => []]),
        ]);

        $this->travelTo(now()->setTime(0, 5));

        try {
            app(JoobleService::class)->fetchAll('Laravel');
        } catch (JoobleQuotaExceededException) {
        }

        $this->travelTo(now()->addDay()->setTime(0, 0, 10));

        $this->assertSame([], app(JoobleService::class)->fetchAll('Laravel'));
    }

    public function test_any_other_too_many_requests_is_a_plain_failure(): void
    {
        Http::fake(['*' => Http::response(['errorCode' => 1], 429)]);

        $this->expectException(RequestException::class);

        try {
            app(JoobleService::class)->fetchAll('Laravel');
        } finally {
            $this->assertFalse(JoobleService::quotaIsExhausted());
        }
    }
}
