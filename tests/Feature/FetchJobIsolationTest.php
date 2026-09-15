<?php

namespace Tests\Feature;

use App\Actions\ProcessJobOfferAction;
use App\DTOs\NormalizedJobOfferDTO;
use App\Jobs\FetchJobOffersJob;
use App\Services\AdzunaService;
use App\Services\Contracts\JobSourceInterface;
use App\Services\JoobleService;
use App\Services\TecnoempleoService;
use App\Support\FetchRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class FetchJobIsolationTest extends TestCase
{
    use RefreshDatabase;

    private RecordingProcessJobOffer $process;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->process = new RecordingProcessJobOffer(new StoredOffers);
        $this->app->instance(ProcessJobOfferAction::class, $this->process);
    }

    /** What lost 66 queries of offers on the first fetch in production. */
    public function test_one_offer_that_throws_costs_that_offer_and_nothing_else(): void
    {
        $this->boards(
            jooble: [['id' => 'first'], ['id' => 'boom'], ['id' => 'third']],
        );

        [$job, $batch] = (new FetchJobOffersJob('Laravel'))->withFakeBatch('batch-1');
        $job->handle($this->process);

        $this->assertSame(['first', 'third'], $this->process->offers->ids);
        $this->assertSame(['jooble' => 1], FetchRun::offerFailures('batch-1'));
        $this->assertSame([], FetchRun::sourceFailures('batch-1'), 'a lost offer is not a board that failed');
    }

    public function test_a_board_that_cannot_be_asked_does_not_stop_the_others(): void
    {
        $this->boards(
            adzuna: new RuntimeException('503'),
            jooble: [['id' => 'from-jooble']],
        );

        [$job] = (new FetchJobOffersJob('Laravel'))->withFakeBatch('batch-2');
        $job->handle($this->process);

        $this->assertSame(['from-jooble'], $this->process->offers->ids);
        $this->assertSame(['adzuna' => 1], FetchRun::sourceFailures('batch-2'));
    }

    private function boards(array|RuntimeException $adzuna = [], array|RuntimeException $jooble = [], array|RuntimeException $tecnoempleo = []): void
    {
        $this->app->instance(AdzunaService::class, new FakeBoard($adzuna));
        $this->app->instance(JoobleService::class, new FakeBoard($jooble));
        $this->app->instance(TecnoempleoService::class, new FakeBoard($tecnoempleo));
    }
}

class FakeBoard implements JobSourceInterface
{
    public function __construct(private readonly array|RuntimeException $offers) {}

    public function fetchAll(string $query, ?string $location = null, ?int $maxPages = null, ?int $sinceDays = null): array
    {
        if ($this->offers instanceof RuntimeException) {
            throw $this->offers;
        }

        return $this->offers;
    }

    public function normalize(array $raw): NormalizedJobOfferDTO
    {
        throw new RuntimeException('not used');
    }
}

/** Mutable on purpose: the readonly action can point at it but not replace it. */
class StoredOffers
{
    public array $ids = [];
}

readonly class RecordingProcessJobOffer extends ProcessJobOfferAction
{
    public function __construct(public StoredOffers $offers) {}

    public function handle(array $item, JobSourceInterface $source, Collection $technologies): bool
    {
        if ($item['id'] === 'boom') {
            throw new RuntimeException('an offer that cannot be stored');
        }

        $this->offers->ids[] = $item['id'];

        return true;
    }
}
