<?php

namespace Tests\Feature;

use App\Enums\CommandStatus;
use App\Enums\CompanyAliasStatus;
use App\Filament\Resources\CommandLogResource\Pages\ListCommandLogs;
use App\Filament\Resources\CompanyAliasResource\Pages\ListCompanyAliases;
use App\Filament\Resources\JobOfferResource\Pages\EditJobOffer;
use App\Filament\Resources\JobOfferResource\Pages\ListJobOffers;
use App\Models\CommandLog;
use App\Models\Company;
use App\Models\CompanyAlias;
use App\Models\JobOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminEnumColumnsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['is_admin' => true]));
    }

    private function offer(): JobOffer
    {
        $company = Company::create(['name' => 'Acme']);

        return JobOffer::create(['company_id' => $company->id, 'title' => 'Dev', 'url' => 'https://example.com/1', 'source' => 'jooble', 'published_at' => now()]);
    }

    public function test_job_offers_list_and_filter(): void
    {
        $offer = $this->offer();

        Livewire::test(ListJobOffers::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$offer])
            ->assertSee('jooble')
            ->filterTable('source', 'jooble')
            ->assertCanSeeTableRecords([$offer]);
    }

    public function test_job_offer_edit_and_save(): void
    {
        $offer = $this->offer();

        Livewire::test(EditJobOffer::class, ['record' => $offer->getRouteKey()])
            ->assertSuccessful()
            ->assertFormSet(['source' => 'jooble'])
            ->fillForm(['title' => 'Senior Dev'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Senior Dev', $offer->fresh()->title);
    }

    public function test_command_logs_list_and_filter(): void
    {
        $log = CommandLog::create(['command' => 'jobs:fetch', 'status' => CommandStatus::Partial, 'started_at' => now(), 'finished_at' => now(), 'stats' => []]);

        Livewire::test(ListCommandLogs::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$log])
            ->filterTable('status', 'partial')
            ->assertCanSeeTableRecords([$log]);
    }

    public function test_company_aliases_list_and_filter(): void
    {
        $company = Company::create(['name' => 'Acme']);
        $alias = CompanyAlias::create(['name' => 'Acme SL', 'company_id' => $company->id, 'pair_key' => 'a|b', 'status' => CompanyAliasStatus::Approved, 'source' => 'gemini']);

        Livewire::test(ListCompanyAliases::class)
            ->assertSuccessful()
            ->removeTableFilters()
            ->assertCanSeeTableRecords([$alias])
            ->filterTable('status', 'approved')
            ->assertCanSeeTableRecords([$alias]);
    }
}
