<?php

namespace Tests\Feature;

use App\Actions\ResolveProvinceAction;
use App\Actions\UpdateCompanyAction;
use App\DTOs\CompanyDTO;
use App\Models\Company;
use App\Models\Province;
use Database\Seeders\ProvinceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolveProvinceTest extends TestCase
{
    use RefreshDatabase;

    private ResolveProvinceAction $resolve;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(ProvinceSeeder::class);

        $this->resolve = app(ResolveProvinceAction::class);
    }

    public function test_it_uses_the_province_field_when_it_matches(): void
    {
        $this->assertSame($this->id('Barcelona'), $this->resolve->handle('Barcelona'));
    }

    public function test_it_falls_back_to_the_city(): void
    {
        $this->assertSame($this->id('Madrid'), $this->resolve->handle(null, 'Madrid'));
    }

    public function test_it_falls_back_to_the_location_string(): void
    {
        $this->assertSame($this->id('Sevilla'), $this->resolve->handle(null, null, 'Nervión, Sevilla, España'));
    }

    public function test_it_ignores_accents_and_case(): void
    {
        $this->assertSame($this->id('Málaga'), $this->resolve->handle(null, 'malaga'));
        $this->assertSame($this->id('A Coruña'), $this->resolve->handle(null, 'LA CORUNA'));
    }

    public function test_it_maps_cities_that_are_not_named_after_their_province(): void
    {
        $this->assertSame($this->id('Vizcaya'), $this->resolve->handle(null, 'Bilbao'));
        $this->assertSame($this->id('Illes Balears'), $this->resolve->handle(null, 'Palma de Mallorca'));
        $this->assertSame($this->id('Pontevedra'), $this->resolve->handle(null, 'Vigo'));
    }

    public function test_it_maps_single_province_communities(): void
    {
        $this->assertSame($this->id('Madrid'), $this->resolve->handle('Comunidad de Madrid'));
        $this->assertSame($this->id('Asturias'), $this->resolve->handle('Principado de Asturias'));
    }

    public function test_it_refuses_to_guess(): void
    {
        $this->assertNull($this->resolve->handle('Cataluña'));
        $this->assertNull($this->resolve->handle('Andalucía'));

        $this->assertNull($this->resolve->handle(null, 'Teletrabajo'));
        $this->assertNull($this->resolve->handle(null, 'España'));
        $this->assertNull($this->resolve->handle(null, ''));
        $this->assertNull($this->resolve->handle(null, null, null));
    }

    public function test_updating_a_company_never_erases_what_it_already_knew(): void
    {
        $company = Company::create([
            'name'        => 'Acme',
            'city'        => 'Barcelona',
            'province_id' => $this->id('Barcelona'),
            'latitude'    => 41.3874,
            'longitude'   => 2.1686,
        ]);

        app(UpdateCompanyAction::class)->handle($company, new CompanyDTO(
            name: 'Acme',
            location: 'Teletrabajo',
            country: 'Spain',
            city: null,
            province: null,
            latitude: null,
            longitude: null,
        ));

        $company->refresh();

        $this->assertSame($this->id('Barcelona'), $company->province_id);
        $this->assertSame('Barcelona', $company->city);
        $this->assertEquals(41.3874, (float) $company->latitude);
    }

    private function id(string $name): int
    {
        return Province::query()->where('name', $name)->value('id');
    }
}
