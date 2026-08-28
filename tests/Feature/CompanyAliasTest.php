<?php

namespace Tests\Feature;

use App\Actions\FindCompanyAliasCandidatesAction;
use App\Actions\MergeCompaniesAction;
use App\Actions\ResolveCompanyAction;
use App\DTOs\CompanyDTO;
use App\Models\Company;
use App\Models\CompanyAlias;
use App\Models\JobOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_name_reading_inside_another_becomes_a_candidate(): void
    {
        $otis = Company::create(['name' => 'Otis']);
        Company::create(['name' => 'Otis Elevator Company']);

        $candidates = app(FindCompanyAliasCandidatesAction::class)->handle();

        $this->assertCount(1, $candidates);
        $this->assertSame($otis->id, $candidates->first()['survivor']->id);
        $this->assertSame('Otis Elevator Company', $candidates->first()['absorbed']->name);
    }

    public function test_a_name_inside_a_single_word_is_not_a_candidate(): void
    {
        Company::create(['name' => 'Sap']);
        Company::create(['name' => 'Sapiens']);

        $rules = app(FindCompanyAliasCandidatesAction::class)
            ->handle()
            ->pluck('rule');

        $this->assertNotContains('contained', $rules);
    }

    public function test_a_name_in_the_middle_of_another_becomes_a_candidate(): void
    {
        Company::create(['name' => 'Oesía']);
        Company::create(['name' => 'Grupo Oesia Networks']);

        $candidates = app(FindCompanyAliasCandidatesAction::class)->handle();

        $this->assertSame('Oesía', $candidates->first()['survivor']->name);
    }

    public function test_a_pair_already_decided_is_never_asked_about_again(): void
    {
        $airbus = Company::create(['name' => 'Airbus']);
        Company::create(['name' => 'Airbus Defence and Space']);

        CompanyAlias::create([
            'name'       => 'Airbus Defence and Space',
            'company_id' => $airbus->id,
            'pair_key'   => CompanyAlias::pairKey('Airbus', 'Airbus Defence and Space'),
            'status'     => CompanyAlias::REJECTED,
        ]);

        $this->assertCount(0, app(FindCompanyAliasCandidatesAction::class)->handle());
    }

    public function test_merging_moves_the_offers_and_keeps_the_shorter_name(): void
    {
        $otis     = Company::create(['name' => 'Otis']);
        $absorbed = Company::create(['name' => 'Otis Elevator Company']);

        $offer = JobOffer::create([
            'company_id' => $absorbed->id,
            'title'      => 'Técnico de ascensores',
            'url'        => 'https://example.test/1',
            'source'     => 'jooble',
        ]);

        app(MergeCompaniesAction::class)->handle($absorbed, $otis);

        $this->assertSame($otis->id, $offer->fresh()->company_id);
        $this->assertNull(Company::find($absorbed->id));
        $this->assertSame('Otis', Company::sole()->name);
    }

    public function test_merging_keeps_the_enrichment_that_was_paid_for(): void
    {
        $survivor = Company::create(['name' => 'Otis']);
        $absorbed = Company::create([
            'name'            => 'Otis Elevator Company',
            'sector'          => 'industry',
            'website'         => 'https://otis.com',
            'gemini_enriched' => true,
        ]);

        app(MergeCompaniesAction::class)->handle($absorbed, $survivor);

        $survivor->refresh();

        $this->assertSame('industry', $survivor->sector);
        $this->assertSame('https://otis.com', $survivor->website);
        $this->assertTrue((bool) $survivor->gemini_enriched);
    }

    public function test_merging_never_overwrites_what_the_survivor_already_has(): void
    {
        $survivor = Company::create(['name' => 'Otis', 'sector' => 'industry']);
        $absorbed = Company::create(['name' => 'Otis Elevator Company', 'sector' => 'it_consulting']);

        app(MergeCompaniesAction::class)->handle($absorbed, $survivor);

        $this->assertSame('industry', $survivor->fresh()->sector);
    }

    public function test_a_merged_name_is_not_recreated_by_the_next_fetch(): void
    {
        $survivor = Company::create(['name' => 'Otis']);
        $absorbed = Company::create(['name' => 'Otis Elevator Company']);

        app(MergeCompaniesAction::class)->handle($absorbed, $survivor);

        $resolved = app(ResolveCompanyAction::class)->handle($this->dto('Otis Elevator Company'));

        $this->assertSame($survivor->id, $resolved->id);
        $this->assertSame(1, Company::count());
    }

    public function test_a_rejected_pair_does_not_join_the_two_companies(): void
    {
        $airbus = Company::create(['name' => 'Airbus']);

        CompanyAlias::create([
            'name'       => 'Airbus Defence and Space',
            'company_id' => $airbus->id,
            'pair_key'   => CompanyAlias::pairKey('Airbus', 'Airbus Defence and Space'),
            'status'     => CompanyAlias::REJECTED,
        ]);

        app(ResolveCompanyAction::class)->handle($this->dto('Airbus Defence and Space'));

        $this->assertSame(2, Company::count());
    }

    public function test_a_merge_can_be_undone(): void
    {
        $survivor = Company::create(['name' => 'Otis']);
        $absorbed = Company::create(['name' => 'Otis Elevator Company']);

        $offer = JobOffer::create([
            'company_id' => $absorbed->id,
            'title'      => 'Técnico de ascensores',
            'url'        => 'https://example.test/1',
            'source'     => 'jooble',
        ]);

        $alias = app(MergeCompaniesAction::class)->handle($absorbed, $survivor);

        $restored = app(MergeCompaniesAction::class)->undo($alias);

        $this->assertSame('Otis Elevator Company', $restored->name);
        $this->assertSame($restored->id, $offer->fresh()->company_id);
        $this->assertSame(CompanyAlias::REJECTED, $alias->fresh()->status);
        $this->assertSame(2, Company::count());
    }

    public function test_an_alias_follows_the_company_it_points_to_through_a_second_merge(): void
    {
        $shortest = Company::create(['name' => 'Otis']);
        $middle   = Company::create(['name' => 'Otis Elevator Co']);
        $longest  = Company::create(['name' => 'Otis Elevator Company']);

        $merge = app(MergeCompaniesAction::class);
        $alias = $merge->handle($longest, $middle);
        $merge->handle($middle, $shortest);

        $this->assertSame($shortest->id, $alias->fresh()->company_id);

        $resolved = app(ResolveCompanyAction::class)->handle($this->dto('Otis Elevator Company'));

        $this->assertSame($shortest->id, $resolved->id);
    }

    private function dto(string $name): CompanyDTO
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
