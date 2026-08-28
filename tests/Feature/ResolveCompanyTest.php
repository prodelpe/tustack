<?php

namespace Tests\Feature;

use App\Actions\ResolveCompanyAction;
use App\DTOs\CompanyDTO;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveCompanyTest extends TestCase
{
    use RefreshDatabase;

    private ResolveCompanyAction $resolve;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolve = app(ResolveCompanyAction::class);
    }

    public function test_a_legal_form_does_not_make_a_second_company(): void
    {
        $first  = $this->resolve->handle($this->company('Excelia'));
        $second = $this->resolve->handle($this->company('Excelia, SL'));

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Company::count());
    }

    public function test_punctuation_does_not_make_a_second_company(): void
    {
        $first  = $this->resolve->handle($this->company('Sopra Steria'));
        $second = $this->resolve->handle($this->company('Sopra-Steria'));

        $this->assertSame($first->id, $second->id);
    }

    public function test_accents_and_case_do_not_make_a_second_company(): void
    {
        $first  = $this->resolve->handle($this->company('Grupo Oesía'));
        $second = $this->resolve->handle($this->company('GRUPO OESIA'));

        $this->assertSame($first->id, $second->id);
    }

    public function test_the_name_first_seen_is_the_one_kept(): void
    {
        $this->resolve->handle($this->company('NexTReT'));
        $this->resolve->handle($this->company('NexTReT, S.L.'));

        $this->assertSame('NexTReT', Company::sole()->name);
    }

    public function test_different_companies_stay_apart(): void
    {
        $this->resolve->handle($this->company('Adyen'));
        $this->resolve->handle($this->company('Aderen'));

        $this->assertSame(2, Company::count());
    }

    public function test_a_name_that_is_only_a_legal_form_is_not_emptied(): void
    {
        $this->resolve->handle($this->company('SA'));
        $this->resolve->handle($this->company('SL'));

        $this->assertSame(2, Company::count());
    }

    public function test_names_outside_the_latin_alphabet_do_not_pile_up_together(): void
    {
        $this->resolve->handle($this->company('慨正橡扯'));
        $this->resolve->handle($this->company('??????????'));

        $this->assertSame(2, Company::count());
    }

    private function company(string $name): CompanyDTO
    {
        return new CompanyDTO(
            name: $name,
            location: null,
            city: null,
            province: null,
            country: null,
            latitude: null,
            longitude: null,
        );
    }
}
