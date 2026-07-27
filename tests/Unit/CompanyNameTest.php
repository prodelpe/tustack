<?php

namespace Tests\Unit;

use App\Support\CompanyName;
use PHPUnit\Framework\TestCase;

class CompanyNameTest extends TestCase
{
    public function test_it_capitalises_names_written_in_lowercase(): void
    {
        $this->assertSame('Knowmad Mood', CompanyName::display('knowmad mood'));
        $this->assertSame('Domestiko.com', CompanyName::display('domestiko.com'));
    }

    public function test_it_calms_names_shouted_in_capitals(): void
    {
        $this->assertSame('Capitole Consulting', CompanyName::display('CAPITOLE CONSULTING'));
        $this->assertSame('Mobius Group', CompanyName::display('MOBIUS GROUP'));
    }

    public function test_it_leaves_acronyms_alone(): void
    {
        $this->assertSame('IBM', CompanyName::display('IBM'));
        $this->assertSame('SAP', CompanyName::display('SAP'));
        $this->assertSame('KPMG', CompanyName::display('KPMG'));
        $this->assertSame('EY GLOBAL', CompanyName::display('EY GLOBAL'));
    }

    public function test_it_leaves_deliberate_capitals_alone(): void
    {
        $this->assertSame('eBay', CompanyName::display('eBay'));
        $this->assertSame('T-Systems Iberia', CompanyName::display('T-Systems Iberia'));
        $this->assertSame('Grupo Oesía', CompanyName::display('Grupo Oesía'));
    }

    public function test_it_keeps_particles_down_and_legal_forms_up(): void
    {
        $this->assertSame('Alovia Consulting, S.L', CompanyName::display('alovia consulting, s.l'));
        $this->assertSame('Soluciones de Gestion y Desarrollo', CompanyName::display('soluciones de gestion y desarrollo'));
    }

    public function test_it_survives_odd_input(): void
    {
        $this->assertSame('', CompanyName::display(''));
        $this->assertSame('123', CompanyName::display('123'));
        $this->assertSame('---', CompanyName::display('---'));
    }
}
