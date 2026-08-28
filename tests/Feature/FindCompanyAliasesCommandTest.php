<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyAlias;
use App\Models\JobOffer;
use App\Support\CompanyName;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FindCompanyAliasesCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.gemini.enabled' => true, 'services.gemini.api_key' => 'a-key']);
    }

    /**
     * Roche absorbs F. Hoffmann-La Roche AG, and the next pair in the same run
     * would give F. Hoffmann-La Roche AG the Gruppe. The candidates were worked
     * out before any of it happened, so that second pair points at a company
     * that no longer exists.
     */
    public function test_a_company_absorbed_earlier_in_the_run_does_not_break_the_rest(): void
    {
        $this->companyWithAnOffer('Roche');
        $this->companyWithAnOffer('Roche Holding AG');
        $this->companyWithAnOffer('Roche Holding AG Gruppe');

        $this->geminiAlwaysSaysYes();

        $this->artisan('companies:find-aliases')->assertExitCode(0);

        $this->assertSame(1, Company::count());
        $this->assertSame('Roche', Company::sole()->name);
        $this->assertSame(3, JobOffer::whereNotNull('company_id')->count());
    }

    public function test_every_absorbed_name_still_leads_home(): void
    {
        $this->companyWithAnOffer('Roche');
        $this->companyWithAnOffer('Roche Holding AG');
        $this->companyWithAnOffer('Roche Holding AG Gruppe');

        $this->geminiAlwaysSaysYes();

        $this->artisan('companies:find-aliases');

        $survivor = Company::sole();

        foreach (['Roche Holding AG', 'Roche Holding AG Gruppe'] as $name) {
            $this->assertTrue(
                CompanyAlias::companyFor(CompanyName::normalize($name))?->is($survivor),
                $name . ' no longer leads to the surviving company'
            );
        }
    }

    /**
     * Resolving a chain can swap which of the two companies is still standing,
     * and the name on the page must not get longer because of it.
     */
    public function test_the_shortest_name_still_wins_after_a_chain_of_merges(): void
    {
        $this->companyWithAnOffer('Avanade Spain SL');
        $this->companyWithAnOffer('Avanade');
        $this->companyWithAnOffer('Avanade Spain SLU Company');

        $this->geminiAlwaysSaysYes();

        $this->artisan('companies:find-aliases')->assertExitCode(0);

        $this->assertSame('Avanade', Company::sole()->name);
        $this->assertSame(3, JobOffer::whereNotNull('company_id')->count());
    }

    public function test_a_refusal_is_remembered_so_the_pair_is_never_paid_for_twice(): void
    {
        $this->companyWithAnOffer('Siemens');
        $this->companyWithAnOffer('Siemens Energy');

        $this->geminiAlwaysSaysNo();

        $this->artisan('companies:find-aliases');

        $this->assertSame(2, Company::count());
        $this->assertSame(1, CompanyAlias::where('status', CompanyAlias::REJECTED)->count());

        // Second run: the pair is already decided, so nothing is asked again.
        Http::fake();

        $this->artisan('companies:find-aliases')
            ->expectsOutputToContain('No new company names to ask about.');

        Http::assertNothingSent();
    }

    private function companyWithAnOffer(string $name): Company
    {
        $company = Company::create(['name' => $name]);

        JobOffer::create([
            'company_id' => $company->id,
            'title'      => 'Backend Developer',
            'url'        => 'https://example.test/' . md5($name),
            'source'     => 'jooble',
        ]);

        return $company;
    }

    private function geminiAlwaysSaysYes(): void
    {
        $this->fakeGeminiWith(true);
    }

    private function geminiAlwaysSaysNo(): void
    {
        $this->fakeGeminiWith(false);
    }

    private function fakeGeminiWith(bool $same): void
    {
        $answers = collect(range(0, 39))
            ->map(fn (int $index) => ['index' => $index, 'same' => $same])
            ->all();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => json_encode($answers)]]]],
                ],
            ]),
        ]);
    }
}
