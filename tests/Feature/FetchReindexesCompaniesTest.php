<?php

namespace Tests\Feature;

use App\Actions\ProcessJobOfferAction;
use App\DTOs\NormalizedJobOfferDTO;
use App\Enums\JobSource;
use App\Jobs\FetchJobOffersJob;
use App\Models\Company;
use App\Models\Technology;
use App\Services\AdzunaService;
use App\Services\Contracts\JobSourceInterface;
use App\Services\JoobleService;
use App\Services\TecnoempleoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\Engine;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class FetchReindexesCompaniesTest extends TestCase
{
    use RefreshDatabase;

    private MockInterface $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = Mockery::spy(Engine::class);

        $this->app->make(EngineManager::class)->extend('spy', fn () => $this->engine);
        config(['scout.driver' => 'spy']);

        Technology::create(['name' => 'Laravel', 'slug' => 'laravel']);

        $this->app->instance(AdzunaService::class, new BoardWithOffers([]));
        $this->app->instance(TecnoempleoService::class, new BoardWithOffers([]));
    }

    /** Half the catalogue was missing from the search: 3,080 of 6,542 companies. */
    public function test_a_company_that_arrives_with_its_first_offer_reaches_the_search(): void
    {
        $this->app->instance(JoobleService::class, new BoardWithOffers([
            ['url' => 'https://example.com/1', 'title' => 'Laravel developer', 'company' => 'Acme'],
        ]));

        (new FetchJobOffersJob('Laravel'))->handle(app(ProcessJobOfferAction::class));

        $this->engine->shouldHaveReceived('update')->withArgs(function (Collection $companies) {
            $acme = $companies->firstWhere('name', 'Acme');

            return $acme !== null && $acme->toSearchableArray()['technology_names'] === ['Laravel'];
        });
    }

    public function test_a_company_whose_offers_did_not_change_is_left_alone(): void
    {
        $this->app->instance(JoobleService::class, new BoardWithOffers([
            ['url' => 'https://example.com/1', 'title' => 'Laravel developer', 'company' => 'Acme'],
        ]));

        (new FetchJobOffersJob('Laravel'))->handle(app(ProcessJobOfferAction::class));

        Company::query()->firstWhere('name', 'Acme')->jobOffers()->toBase()->update(['updated_at' => now()->subDay()]);

        $this->engine = Mockery::spy(Engine::class);
        $this->app->forgetInstance(EngineManager::class);
        $this->app->make(EngineManager::class)->extend('spy', fn () => $this->engine);

        $this->app->instance(JoobleService::class, new BoardWithOffers([]));

        (new FetchJobOffersJob('Laravel'))->handle(app(ProcessJobOfferAction::class));

        $this->engine->shouldNotHaveReceived('update');
    }
}

class BoardWithOffers implements JobSourceInterface
{
    public function __construct(private readonly array $offers) {}

    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null, ?int $sinceDays = null): array
    {
        return $this->offers;
    }

    public function normalize(array $raw): NormalizedJobOfferDTO
    {
        return new NormalizedJobOfferDTO(
            url: $raw['url'],
            source: JobSource::Jooble,
            title: $raw['title'],
            company: $raw['company'],
            location: null,
            city: null,
            province: null,
            country: 'Spain',
            latitude: null,
            longitude: null,
            salaryMin: null,
            salaryMax: null,
            salaryIsPredicted: null,
            description: null,
            publishedAt: now()->toDateString(),
        );
    }
}
