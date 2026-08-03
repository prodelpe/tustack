<?php

namespace Tests\Feature;

use App\Services\AdzunaService;
use App\Services\JoobleService;
use App\Services\TecnoempleoService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class IncrementalFetchTest extends TestCase
{
    public function test_jooble_asks_only_for_offers_newer_than_the_cutoff(): void
    {
        Http::fake(['*' => Http::response(['totalCount' => 0, 'jobs' => []])]);

        app(JoobleService::class)->fetchAll('Laravel', sinceDays: 4);

        Http::assertSent(function ($request) {
            return $request['datecreatedfrom'] === now()->subDays(4)->toDateString();
        });
    }

    public function test_jooble_asks_for_everything_when_no_cutoff_is_given(): void
    {
        Http::fake(['*' => Http::response(['totalCount' => 0, 'jobs' => []])]);

        app(JoobleService::class)->fetchAll('Laravel');

        Http::assertSent(fn ($request) => ! isset($request['datecreatedfrom']));
    }

    public function test_adzuna_asks_only_for_offers_newer_than_the_cutoff(): void
    {
        Http::fake(['*' => Http::response(['count' => 0, 'results' => []])]);

        app(AdzunaService::class)->fetchAll('Laravel', sinceDays: 4);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'max_days_old=4'));
    }

    public function test_adzuna_asks_for_everything_when_no_cutoff_is_given(): void
    {
        Http::fake(['*' => Http::response(['count' => 0, 'results' => []])]);

        app(AdzunaService::class)->fetchAll('Laravel');

        Http::assertSent(fn ($request) => ! str_contains($request->url(), 'max_days_old'));
    }

    public function test_tecnoempleo_drops_the_offers_older_than_the_cutoff(): void
    {
        Http::fake(['*' => Http::response($this->page([
            'Fresh one' => now()->subDay(),
            'Old one'   => now()->subDays(40),
        ]))]);

        $offers = app(TecnoempleoService::class)->fetchAll('Laravel', sinceDays: 4);

        $this->assertCount(1, $offers);
        $this->assertSame('Fresh one', $offers[0]['title']);
    }

    public function test_tecnoempleo_stops_asking_for_pages_once_they_turn_old(): void
    {
        Http::fake(['*' => Http::response($this->page(['Old one' => now()->subDays(40)]))]);

        app(TecnoempleoService::class)->fetchAll('Laravel', sinceDays: 4);

        Http::assertSentCount(1);
    }

    public function test_tecnoempleo_keeps_walking_when_everything_is_fresh(): void
    {
        Http::fake(['*' => Http::response($this->page(['Fresh one' => now()->subDay()]))]);

        app(TecnoempleoService::class)->fetchAll('Laravel', maxPages: 3, sinceDays: 4);

        $this->assertGreaterThan(1, Http::recorded()->count());
    }

    private function page(array $titlesWithDates): string
    {
        $cards = '';

        foreach ($titlesWithDates as $title => $date) {
            $cards .= '<div class="p-3 border rounded mb-3 bg-white">'
                . '<h3><a href="https://www.tecnoempleo.com/' . md5($title) . '">' . $title . '</a></h3>'
                . '<a class="text-primary">Acme</a>'
                . '<div class="col-12 col-lg-3"><b>Barcelona</b> ' . $date->format('d/m/Y') . '</div>'
                . '</div>';
        }

        return '<html><body>' . $cards . '</body></html>';
    }
}
