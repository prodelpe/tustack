<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Support\CompanyEnrichmentSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrichmentSnapshotKeyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_legal_form_no_longer_hides_paid_enrichment(): void
    {
        $this->assertSame(
            CompanyEnrichmentSnapshot::key('NexTReT, S.L.'),
            CompanyEnrichmentSnapshot::key('NexTReT'),
        );
    }

    public function test_case_and_accents_still_meet(): void
    {
        $this->assertSame(
            CompanyEnrichmentSnapshot::key('Grupo Oesía'),
            CompanyEnrichmentSnapshot::key('GRUPO OESIA'),
        );
    }

    public function test_the_key_matches_the_column_the_import_looks_up(): void
    {
        $company = Company::create(['name' => 'Sopra-Steria']);

        $this->assertSame(
            CompanyEnrichmentSnapshot::key('Sopra Steria, S.L.'),
            $company->name_normalized,
        );
    }

    public function test_different_companies_keep_different_keys(): void
    {
        $this->assertNotSame(
            CompanyEnrichmentSnapshot::key('Adyen'),
            CompanyEnrichmentSnapshot::key('Aderen'),
        );
    }
}
