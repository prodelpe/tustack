<?php

namespace App\Jobs;

use App\Actions\ProcessJobOfferAction;
use App\Models\Technology;
use App\Services\AdzunaService;
use App\Services\JoobleService;
use App\Services\TecnoempleoService;
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

                foreach ($raw as $item) {
                    $processJobOffer->handle($item, $source, $technologies);
                }
            } catch (Throwable $e) {
                Log::warning("FetchJobOffersJob failed [{$name}] for query [{$this->query}]", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
