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

    public function test_the_same_vacancy_seen_on_two_boards_is_one_offer(): void
    {
        $this->upsert->handle($this->offer(url: 'https://example.test/1', source: 'adzuna'), $this->company);
        $this->upsert->handle($this->offer(url: 'https://example.test/2', source: 'jooble'), $this->company);

        $this->assertSame(1, JobOffer::count());
    }

    public function test_two_boards_are_only_merged_inside_the_window(): void
    {
        $days = config('jobs.cross_source_window_days');

        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            source: 'adzuna',
            publishedAt: now()->subDays($days + 5)->toDateString(),
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/2',
            source: 'jooble',
            publishedAt: now()->toDateString(),
        ), $this->company);

        $this->assertSame(2, JobOffer::count());
    }

    public function test_the_title_comes_from_the_source_that_keeps_its_accents(): void
    {
        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            title: 'Programador Senior',
            source: 'adzuna',
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/2',
            title: 'Programador Sénior',
            source: 'jooble',
        ), $this->company);

        $offer = JobOffer::sole();

        $this->assertSame('Programador Sénior', $offer->title);
        $this->assertSame('jooble', $offer->source);
        $this->assertSame('https://example.test/2', $offer->url);
    }

    public function test_a_worse_source_never_takes_over_the_title(): void
    {
        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            title: 'Programador Sénior',
            source: 'jooble',
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/2',
            title: 'Programador Senior',
            source: 'adzuna',
        ), $this->company);

        $offer = JobOffer::sole();

        $this->assertSame('Programador Sénior', $offer->title);
        $this->assertSame('jooble', $offer->source);
    }

    public function test_the_longest_description_wins(): void
    {
        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            source: 'adzuna',
            description: 'A long and detailed account of the role and the team',
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/2',
            source: 'jooble',
            description: 'Short snippet',
        ), $this->company);

        $this->assertSame(
            'A long and detailed account of the role and the team',
            JobOffer::sole()->description,
        );
    }

    public function test_a_salary_from_either_board_is_kept(): void
    {
        $this->upsert->handle($this->offer(
            url: 'https://example.test/1',
            source: 'adzuna',
        ), $this->company);

        $this->upsert->handle($this->offer(
            url: 'https://example.test/2',
            source: 'jooble',
            salaryMin: 35000,
            salaryMax: 45000,
        ), $this->company);

        $offer = JobOffer::sole();

        $this->assertSame(35000, $offer->salary_min);
        $this->assertSame(45000, $offer->salary_max);
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
        string $description = 'Something about PHP',
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
            description: $description,
            publishedAt: $publishedAt ?? now()->toDateString(),
        );
    }
}
