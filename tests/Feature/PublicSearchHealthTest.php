<?php

namespace Tests\Feature;

use App\Support\PublicSearch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicSearchHealthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'search.host'              => 'https://search.example.test',
            'scout.meilisearch.host'   => 'http://127.0.0.1:7700',
        ]);
    }

    /**
     * The whole point: the internal address answers from the server even when
     * the public one is serving something else entirely.
     */
    public function test_the_check_asks_the_address_the_browser_is_given(): void
    {
        Http::fake(['search.example.test/health' => Http::response(['status' => 'available'])]);

        PublicSearch::viewData();

        Http::assertSent(fn ($request) => $request->url() === 'https://search.example.test/health');
    }

    public function test_a_healthy_public_address_means_available(): void
    {
        Http::fake(['search.example.test/health' => Http::response(['status' => 'available'])]);

        $this->assertTrue(PublicSearch::viewData()['meilisearchAvailable']);
    }

    public function test_a_public_address_serving_something_else_means_unavailable(): void
    {
        Http::fake(['search.example.test/health' => Http::response('<html>Umami</html>', 404)]);

        $this->assertFalse(PublicSearch::viewData()['meilisearchAvailable']);
    }

    public function test_an_unreachable_address_means_unavailable_instead_of_an_error(): void
    {
        Http::fake(fn () => throw new ConnectionException('no route'));

        $this->assertFalse(PublicSearch::viewData()['meilisearchAvailable']);
    }

    public function test_no_address_at_all_means_unavailable(): void
    {
        config(['search.host' => '']);
        Http::fake();

        $this->assertFalse(PublicSearch::viewData()['meilisearchAvailable']);

        Http::assertNothingSent();
    }

    public function test_a_trailing_slash_does_not_produce_a_double_slash(): void
    {
        config(['search.host' => 'https://search.example.test/']);
        Http::fake(['search.example.test/health' => Http::response(['status' => 'available'])]);

        PublicSearch::viewData();

        Http::assertSent(fn ($request) => $request->url() === 'https://search.example.test/health');
    }

    public function test_the_pages_are_not_charged_a_round_trip_each_time(): void
    {
        Http::fake(['search.example.test/health' => Http::response(['status' => 'available'])]);

        PublicSearch::viewData();
        PublicSearch::viewData();
        PublicSearch::viewData();

        Http::assertSentCount(1);
    }
}
