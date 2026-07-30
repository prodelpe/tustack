<?php

namespace Tests\Feature;

use App\Actions\UpsertJobOfferAction;
use App\DTOs\NormalizedJobOfferDTO;
use App\Models\Company;
use App\Models\JobOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpsertJobOfferTest extends TestCase
{
    use RefreshDatabase;

    private UpsertJobOfferAction $upsert;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->upsert  = app(UpsertJobOfferAction::class);
        $this->company = Company::create(['name' => 'Acme']);
    }

    public function test_the_same_url_is_never_stored_twice(): void
    {
        $this->upsert->handle($this->offer(url: 'https://example.test/1'), $this->company);
        $this->upsert->handle($this->offer(url: 'https://example.test/1'), $this->company);

        $this->assertSame(1, JobOffer::count());
    }

    public function test_a_republished_vacancy_updates_the_one_we_had(): void
    {
        $this->upsert->handle($this->offer(
            url: 'https://example.test/old',
            publishedAt: now()->subDays(10)->toDateString(),
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/new',
            publishedAt: now()->toDateString(),
        ), $this->company);

        $this->assertSame(1, JobOffer::count());

        $offer = JobOffer::first();

        $this->assertSame('https://example.test/new', $offer->url);
        $this->assertTrue($offer->published_at->isToday());
    }

    public function test_the_title_is_compared_ignoring_case_and_spacing(): void
    {
        $this->upsert->handle($this->offer(url: 'https://example.test/1', title: 'Backend Developer'), $this->company);
        $this->upsert->handle($this->offer(url: 'https://example.test/2', title: '  backend   developer '), $this->company);

        $this->assertSame(1, JobOffer::count());
    }

    public function test_the_same_title_outside_the_window_is_a_new_vacancy(): void
    {
        $days = config('jobs.duplicate_window_days');

        $this->upsert->handle($this->offer(
            url: 'https://example.test/old',
            publishedAt: now()->subDays($days + 5)->toDateString(),
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/new',
            publishedAt: now()->toDateString(),
        ), $this->company);

        $this->assertSame(2, JobOffer::count());
    }

    public function test_offers_from_different_sources_are_kept_apart(): void
    {
        $this->upsert->handle($this->offer(url: 'https://example.test/1', source: 'adzuna'), $this->company);
        $this->upsert->handle($this->offer(url: 'https://example.test/2', source: 'jooble'), $this->company);

        $this->assertSame(2, JobOffer::count());
    }

    public function test_refreshing_never_drops_details_the_new_reading_lacks(): void
    {
        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            salaryMin: 30000,
            salaryMax: 40000,
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/2',
            salaryMin: null,
            salaryMax: null,
        ), $this->company);

        $offer = JobOffer::first();

        $this->assertSame(30000, $offer->salary_min);
        $this->assertSame(40000, $offer->salary_max);
    }

    public function test_an_implausible_salary_is_not_stored(): void
    {
        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            salaryMin: 55,
            salaryMax: 77,
        ), $this->company);

        $offer = JobOffer::first();

        $this->assertNull($offer->salary_min);
        $this->assertNull($offer->salary_max);
    }

    public function test_an_implausible_salary_never_overwrites_a_good_one(): void
    {
        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            salaryMin: 30000,
            salaryMax: 40000,
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            salaryMin: 22515,
            salaryMax: 89,
        ), $this->company);

        $offer = JobOffer::first();

        $this->assertSame(30000, $offer->salary_min);
        $this->assertSame(40000, $offer->salary_max);
    }

    private function offer(
        string $url,
        string $title = 'Backend Developer',
        string $source = 'adzuna',
        ?string $publishedAt = null,
        ?int $salaryMin = null,
        ?int $salaryMax = null,
    ): NormalizedJobOfferDTO {
        return new NormalizedJobOfferDTO(
            url: $url,
            source: $source,
            title: $title,
            company: 'Acme',
            location: 'Barcelona',
            city: 'Barcelona',
            province: null,
            country: 'Spain',
            latitude: null,
            longitude: null,
            salaryMin: $salaryMin,
            salaryMax: $salaryMax,
            salaryIsPredicted: null,
            description: 'Something about PHP',
            publishedAt: $publishedAt ?? now()->toDateString(),
        );
    }
}
