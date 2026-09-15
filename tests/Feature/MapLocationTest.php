<?php

namespace Tests\Feature;

use App\Actions\EnrichCompanyWithGeminiAction;
use App\Models\Company;
use App\Models\Province;
use App\Support\MapLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapLocationTest extends TestCase
{
    use RefreshDatabase;

    /** Amazon, filed in Madrid with 142 offers there, sat on its data centres in Huesca. */
    public function test_coordinates_in_another_province_give_way_to_the_province_it_is_filed_under(): void
    {
        $company = $this->company('Amazon', 'madrid', 42.1399, -0.4039);

        $this->assertSame(['lat' => 40.4168, 'lng' => -3.7038], MapLocation::for($company));
    }

    public function test_coordinates_that_agree_with_the_province_are_kept(): void
    {
        $company = $this->company('Startup', 'barcelona', 41.4036, 2.1744);

        $this->assertSame(['lat' => 41.4036, 'lng' => 2.1744], MapLocation::for($company));
    }

    /** Lanzarote is far from Las Palmas, but still nearer to it than to any other capital. */
    public function test_an_island_far_from_its_capital_still_counts_as_its_province(): void
    {
        $company = $this->company('Island', 'las-palmas', 28.9630, -13.5477);

        $this->assertSame(['lat' => 28.963, 'lng' => -13.5477], MapLocation::for($company));
    }

    public function test_a_company_without_coordinates_now_appears_at_its_capital(): void
    {
        $company = $this->company('From Jooble', 'valencia', null, null);

        $this->assertSame(['lat' => 39.4699, 'lng' => -0.3763], MapLocation::for($company));
    }

    public function test_without_a_province_the_coordinates_are_all_there_is(): void
    {
        $this->assertSame(
            ['lat' => 40.0, 'lng' => -4.0],
            MapLocation::for(Company::create(['name' => 'Nowhere', 'latitude' => 40.0, 'longitude' => -4.0]))
        );

        $this->assertNull(MapLocation::for(Company::create(['name' => 'Unknown'])));
    }

    public function test_the_search_index_carries_the_corrected_pin(): void
    {
        $company = $this->company('Amazon', 'madrid', 42.1399, -0.4039);

        $this->assertSame(['lat' => 40.4168, 'lng' => -3.7038], $company->fresh()->toSearchableArray()['_geo']);
    }

    public function test_enrichment_no_longer_moves_a_company_that_already_has_coordinates(): void
    {
        $company = $this->company('Amazon', 'madrid', 40.42, -3.70);

        $this->enrichWith(['latitude' => 42.1399, 'longitude' => -0.4039]);
        app(EnrichCompanyWithGeminiAction::class)->handle($company);

        $this->assertEqualsWithDelta(40.42, (float) $company->fresh()->latitude, 0.0001);
        $this->assertEqualsWithDelta(-3.70, (float) $company->fresh()->longitude, 0.0001);
    }

    public function test_enrichment_still_fills_a_company_that_has_none(): void
    {
        $company = $this->company('New', 'madrid', null, null);

        $this->enrichWith(['latitude' => 40.45, 'longitude' => -3.69]);
        app(EnrichCompanyWithGeminiAction::class)->handle($company);

        $this->assertEqualsWithDelta(40.45, (float) $company->fresh()->latitude, 0.0001);
    }

    private function company(string $name, string $provinceSlug, ?float $lat, ?float $lng): Company
    {
        $province = Province::firstOrCreate(['slug' => $provinceSlug], ['name' => ucfirst($provinceSlug)]);

        return Company::create([
            'name'        => $name,
            'province_id' => $province->id,
            'latitude'    => $lat,
            'longitude'   => $lng,
        ])->load('province');
    }

    private function enrichWith(array $data): void
    {
        config(['services.gemini.enabled' => true, 'services.gemini.api_key' => 'test-key']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($data)]]]]],
            ]),
        ]);
    }
}
