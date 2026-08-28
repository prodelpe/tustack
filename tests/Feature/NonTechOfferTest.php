<?php

namespace Tests\Feature;

use App\Actions\ProcessJobOfferAction;
use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Technology;
use App\Services\JoobleService;
use Database\Seeders\TechnologySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * An offer nobody can find by stack is never stored, and neither is the company
 * behind it. This was already the rule; what it needed was a detector that does
 * not read "go-to-market" as a programming language.
 */
class NonTechOfferTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TechnologySeeder::class);
    }

    public function test_a_lift_technician_never_enters_the_catalogue(): void
    {
        $this->process(
            'Otis Elevator Company',
            'TÉCNICO (H/M) DE ASCENSORES',
            'Mantenimiento de ascensores. Buscamos personas que quieran ir más allá.',
        );

        $this->assertSame(0, Company::count());
        $this->assertSame(0, JobOffer::count());
    }

    public function test_a_manager_talking_about_go_to_market_is_not_a_go_offer(): void
    {
        $this->process(
            'Levi Strauss',
            'Product Marketing Manager',
            'You will own the go-to-market strategy and the go-live plan.',
        );

        $this->assertSame(0, Company::count());
    }

    public function test_a_real_offer_still_gets_through(): void
    {
        $this->process(
            'Acme Software',
            'Backend Developer',
            'Our stack is Go and PostgreSQL.',
        );

        $this->assertSame('Acme Software', Company::sole()->name);
        $this->assertSame(1, JobOffer::count());
    }

    private function process(string $company, string $title, string $snippet): void
    {
        app(ProcessJobOfferAction::class)->handle([
            'link'     => 'https://es.jooble.org/away/' . md5($company . $title),
            'title'    => $title,
            'company'  => $company,
            'location' => 'Barcelona, Barcelona',
            'snippet'  => $snippet,
            'updated'  => now()->toDateString(),
        ], app(JoobleService::class), Technology::all()->keyBy(fn (Technology $t) => strtolower($t->name)));
    }
}
