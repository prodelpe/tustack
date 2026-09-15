<?php

namespace App\Jobs;

use App\Actions\ProcessJobOfferAction;
use App\Models\Technology;
use App\Services\AdzunaService;
use App\Services\JoobleService;
use App\Services\TecnoempleoService;
use App\Support\FetchRun;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class FetchJobOffersJob implements ShouldQueue
{
    use Queueable, Batchable;

    public int $timeout = 300;
    public int $tries   = 2;

    public function __construct(
        public readonly string $query,
        public readonly ?int $maxPages = null,
        public readonly ?int $sinceDays = null,
    ) {}

    public function handle(ProcessJobOfferAction $processJobOffer): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $technologies = Technology::all()->keyBy(fn ($t) => strtolower($t->name));

        $sources = [
            'adzuna'      => app(AdzunaService::class),
            'jooble'      => app(JoobleService::class),
            'tecnoempleo' => app(TecnoempleoService::class),
        ];

        foreach ($sources as $name => $source) {
            try {
                $raw = $source->fetchAll($this->query, null, $this->maxPages, $this->sinceDays);
            } catch (Throwable $e) {
                Log::warning("FetchJobOffersJob failed [{$name}] for query [{$this->query}]", [
                    'error' => $e->getMessage(),
                ]);

                if ($this->batch()) {
                    FetchRun::recordSourceFailure($this->batch()->id, $name);
                }

                continue;
            }

            // One offer that throws must cost that offer and nothing else. Before,
            // the exception left the loop and every offer after it was lost: 66
            // queries on the first fetch in production.
            foreach ($raw as $item) {
                try {
                    $processJobOffer->handle($item, $source, $technologies);
                } catch (Throwable $e) {
                    Log::warning("FetchJobOffersJob could not store an offer [{$name}] for query [{$this->query}]", [
                        'error' => $e->getMessage(),
                    ]);

                    if ($this->batch()) {
                        FetchRun::recordOfferFailure($this->batch()->id, $name);
                    }
                }
            }
        }
    }
}
