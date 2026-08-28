<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Tests\TestCase;

class CompanyStackTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([LocaleSessionRedirect::class, LaravelLocalizationRedirectFilter::class]);

        $this->company = Company::create(['name' => 'Acme']);
    }

    public function test_the_stack_leads_with_what_the_company_publishes_most(): void
    {
        $this->offersFor('Java', 3);
        $this->offersFor('PHP', 1);
        $this->offersFor('React', 5);

        $stack = $this->company->technologyStack();

        $this->assertSame(['React', 'Java', 'PHP'], $stack->pluck('technology.name')->all());
        $this->assertSame([5, 3, 1], $stack->pluck('offers')->all());
    }

    public function test_the_stack_says_when_a_technology_was_last_seen(): void
    {
        $this->offersFor('Java', 1, now()->subYears(2));
        $this->offersFor('Java', 1, now()->subMonth());

        $stack = $this->company->technologyStack();

        $this->assertSame(
            now()->subMonth()->toDateString(),
            $stack->first()['last_offer_at']->toDateString()
        );
    }

    public function test_a_company_with_no_technology_stays_out_of_the_catalogue(): void
    {
        JobOffer::create([
            'company_id' => $this->company->id,
            'title'      => 'Ascensorista',
            'url'        => 'https://example.test/lift',
            'source'     => 'jooble',
        ]);

        $this->assertFalse($this->company->fresh()->shouldBeSearchable());
        $this->assertSame(0, Company::query()->inCatalogue()->count());
    }

    public function test_a_company_with_a_stack_belongs_to_the_catalogue(): void
    {
        $this->offersFor('Java', 1);

        $this->assertTrue($this->company->fresh()->shouldBeSearchable());
        $this->assertSame(1, Company::query()->inCatalogue()->count());
    }

    public function test_the_sitemap_leaves_out_a_company_with_no_stack(): void
    {
        JobOffer::create([
            'company_id' => $this->company->id,
            'title'      => 'Ascensorista',
            'url'        => 'https://example.test/lift',
            'source'     => 'jooble',
        ]);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertDontSee('/empresas/acme');
    }

    public function test_the_page_leads_with_the_busiest_technology(): void
    {
        $this->offersFor('Java', 1);
        $this->offersFor('React', 4);

        $response = $this->get(route('companies.show', $this->company));

        $response->assertOk();
        $response->assertSeeInOrder(['React', 'Java']);
    }

    public function test_a_page_with_no_stack_says_so_instead_of_showing_nothing(): void
    {
        JobOffer::create([
            'company_id' => $this->company->id,
            'title'      => 'Ascensorista',
            'url'        => 'https://example.test/lift',
            'source'     => 'jooble',
        ]);

        $this->get(route('companies.show', $this->company))
            ->assertOk()
            ->assertSee(__('company.no_stack'));
    }

    public function test_a_recruiter_is_not_told_to_have_that_stack(): void
    {
        $this->company->update(['sector' => 'recruitment']);
        $this->offersFor('Java', 1);

        $this->get(route('companies.show', $this->company))
            ->assertOk()
            ->assertSee(__('company.tech_stack_recruiter'));
    }

    private function offersFor(string $name, int $count, $publishedAt = null): void
    {
        $technology = Technology::firstOrCreate(
            ['slug' => strtolower($name)],
            ['name' => $name]
        );

        for ($i = 0; $i < $count; $i++) {
            $offer = JobOffer::create([
                'company_id'   => $this->company->id,
                'title'        => $name . ' Developer',
                'url'          => 'https://example.test/' . $name . '-' . $i . '-' . uniqid(),
                'source'       => 'jooble',
                'published_at' => $publishedAt ?? now()->subDays($i),
            ]);

            $offer->technologies()->attach($technology);
        }
    }
}
