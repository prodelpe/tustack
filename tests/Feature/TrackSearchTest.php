<?php

namespace Tests\Feature;

use App\Models\SearchLog;
use App\Models\Technology;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->detectEnvironment(fn () => 'production');
        config(['analytics.enabled' => true]);

        Technology::create(['name' => 'Laravel', 'slug' => 'laravel']);
        Technology::create(['name' => 'Vue', 'slug' => 'vue']);
    }

    public function test_a_search_is_counted_and_logged(): void
    {
        $this->postJson(route('track-search'), [
            'technologies' => ['Laravel', 'Vue'],
            'province'     => 'Barcelona',
        ])->assertNoContent();

        $this->assertSame(1, Technology::where('name', 'Laravel')->value('searches_count'));
        $this->assertSame(1, Technology::where('name', 'Vue')->value('searches_count'));

        $log = SearchLog::sole();

        $this->assertSame(['Laravel', 'Vue'], $log->technologies);
        $this->assertSame('Barcelona', $log->province);
        $this->assertNotNull($log->created_at);
    }

    public function test_the_province_is_optional(): void
    {
        $this->postJson(route('track-search'), ['technologies' => ['Laravel']])->assertNoContent();

        $this->assertNull(SearchLog::sole()->province);
    }

    public function test_technologies_we_do_not_know_are_ignored(): void
    {
        $this->postJson(route('track-search'), ['technologies' => ['Cobol']])->assertNoContent();

        $this->assertSame(0, SearchLog::count());
    }

    public function test_nothing_is_recorded_outside_production(): void
    {
        $this->app->detectEnvironment(fn () => 'local');

        $this->postJson(route('track-search'), ['technologies' => ['Laravel']])->assertNoContent();

        $this->assertSame(0, SearchLog::count());
        $this->assertSame(0, Technology::where('name', 'Laravel')->value('searches_count'));
    }

    public function test_nothing_is_recorded_while_analytics_are_switched_off(): void
    {
        config(['analytics.enabled' => false]);

        $this->postJson(route('track-search'), ['technologies' => ['Laravel']])->assertNoContent();

        $this->assertSame(0, SearchLog::count());
        $this->assertSame(0, Technology::where('name', 'Laravel')->value('searches_count'));
    }

    public function test_nothing_is_recorded_for_the_admin(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('track-search'), ['technologies' => ['Laravel']])
            ->assertNoContent();

        $this->assertSame(0, SearchLog::count());
    }

    public function test_a_search_without_technologies_is_rejected(): void
    {
        $this->postJson(route('track-search'), ['technologies' => []])->assertStatus(422);
    }
}
