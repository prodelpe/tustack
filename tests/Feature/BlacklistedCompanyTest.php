<?php

namespace Tests\Feature;

use App\Actions\ProcessJobOfferAction;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Technology;
use App\Services\JoobleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlacklistedCompanyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Technology::create(['name' => 'Laravel', 'slug' => 'laravel']);
    }

    public function test_an_aggregator_never_becomes_a_company(): void
    {
        foreach (['Jobleads', 'JOBTOME', 'Doméstiko.com', 'Jobtailor', 'Tamarind Intelligence', 'Elpuertodesantamaria'] as $name) {
            $this->process($name);
        }

        $this->assertSame(0, Company::count());
        $this->assertSame(0, JobOffer::count());
    }

    public function test_the_list_can_be_changed_without_touching_code(): void
    {
        $this->process('Acme Software');
        $this->assertSame(1, Company::count());

        config(['jobs.blacklisted_companies' => ['acme software']]);

        $this->process('Acme Software Two');
        $this->assertSame(1, Company::count());
    }

    public function test_a_real_company_still_gets_through(): void
    {
        $this->process('Acme Software');

        $this->assertSame(1, Company::count());
        $this->assertSame('Acme Software', Company::sole()->name);
    }

    private function process(string $company): void
    {
        $technologies = Technology::all()->keyBy(fn ($t) => strtolower($t->name));

        app(ProcessJobOfferAction::class)->handle([
            'link'     => 'https://es.jooble.org/away/' . md5($company),
            'title'    => 'Laravel Developer',
            'company'  => $company,
            'location' => 'Barcelona, Barcelona',
            'snippet'  => 'We work with Laravel every day.',
            'updated'  => now()->toDateString(),
        ], app(JoobleService::class), $technologies);
    }
}
