<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\JobOffer;
use App\Models\Technology;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TechnologyWeightTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_index_lists_technologies_by_how_often_the_company_asks_for_them(): void
    {
        $company = Company::create(['name' => 'Acme']);

        $this->offers($company, 'PHP', 1);
        $this->offers($company, 'Java', 4);
        $this->offers($company, 'React', 2);

        $indexed = $company->fresh()->toSearchableArray();

        $this->assertSame(['Java', 'React', 'PHP'], $indexed['technology_names']);
        $this->assertCount(3, $indexed['technology_ids']);
    }

    private function offers(Company $company, string $name, int $count): void
    {
        $technology = Technology::firstOrCreate(['slug' => strtolower($name)], ['name' => $name]);

        for ($i = 0; $i < $count; $i++) {
            JobOffer::create([
                'company_id' => $company->id,
                'title'      => "{$name} developer",
                'url'        => "https://example.test/{$name}-{$i}",
                'source'     => 'jooble',
            ])->technologies()->attach($technology);
        }
    }
}
