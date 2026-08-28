<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobOffer;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActiveOfferCountTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::create(['name' => 'Acme']);
    }

    public function test_the_index_carries_both_the_active_and_the_stored_count(): void
    {
        $this->offer(now()->subMonth());
        $this->offer(now()->subMonths(2));
        $this->offer(now()->subYears(3));

        $indexed = $this->company->fresh()->toSearchableArray();

        $this->assertSame(2, $indexed['active_offers_count']);
        $this->assertSame(3, $indexed['job_offers_count']);
    }

    public function test_nothing_recent_leaves_the_active_count_at_zero(): void
    {
        $this->offer(now()->subYears(3));

        $indexed = $this->company->fresh()->toSearchableArray();

        $this->assertSame(0, $indexed['active_offers_count']);
        $this->assertSame(1, $indexed['job_offers_count']);
    }

    public function test_the_window_comes_from_the_configuration(): void
    {
        $this->offer(now()->subMonths(8));

        $this->assertSame(1, $this->company->fresh()->toSearchableArray()['active_offers_count']);

        config(['jobs.active_offer_months' => 6]);

        $this->assertSame(0, $this->company->fresh()->toSearchableArray()['active_offers_count']);
    }

    public function test_the_relation_and_the_index_agree(): void
    {
        $this->offer(now()->subMonth());
        $this->offer(now()->subYears(3));

        $company = $this->company->fresh();

        $this->assertSame(
            $company->toSearchableArray()['active_offers_count'],
            $company->activeJobOffers()->count(),
        );
    }

    public function test_an_offer_without_a_date_is_not_counted_as_active(): void
    {
        $this->offer(null);

        $company = $this->company->fresh();

        $this->assertSame(0, $company->toSearchableArray()['active_offers_count']);
        $this->assertSame(1, $company->toSearchableArray()['job_offers_count']);
    }

    private function offer(?DateTimeInterface $publishedAt): JobOffer
    {
        return JobOffer::create([
            'company_id'   => $this->company->id,
            'title'        => 'Backend Developer ' . fake()->uuid(),
            'url'          => 'https://example.test/' . fake()->uuid(),
            'source'       => 'adzuna',
            'published_at' => $publishedAt,
        ]);
    }
}
