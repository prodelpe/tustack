<?php

namespace Tests\Feature;

use App\Services\AdzunaService;
use App\Services\JoobleService;
use App\Services\TecnoempleoService;
use Tests\TestCase;

class JobSourceUrlTest extends TestCase
{
    public function test_jooble_strips_the_tracking_parameters(): void
    {
        $dto = app(JoobleService::class)->normalize([
            'link'     => 'https://es.jooble.org/away/123?p=1&pos=12&ckey=.NET',
            'title'    => 'Backend Developer',
            'location' => 'Madrid, Madrid',
        ]);

        $this->assertSame('https://es.jooble.org/away/123', $dto->url);
    }

    public function test_adzuna_strips_the_tracking_parameters(): void
    {
        $dto = app(AdzunaService::class)->normalize([
            'redirect_url' => 'https://www.adzuna.es/details/5776490536?utm_medium=api',
            'title'        => 'Backend Developer',
        ]);

        $this->assertSame('https://www.adzuna.es/details/5776490536', $dto->url);
    }

    public function test_tecnoempleo_strips_the_tracking_parameters(): void
    {
        $dto = app(TecnoempleoService::class)->normalize([
            'url'   => 'https://www.tecnoempleo.com/developer-net/net-core/rf-abc123?utm_source=rss',
            'title' => 'Backend Developer',
        ]);

        $this->assertSame('https://www.tecnoempleo.com/developer-net/net-core/rf-abc123', $dto->url);
    }
}
