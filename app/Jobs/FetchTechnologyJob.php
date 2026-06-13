<?php

namespace App\Jobs;

use App\Actions\ProcessJobOfferAction;
use App\Models\Technology;
use App\Services\AdzunaService;
use App\Services\Contracts\JobSourceInterface;
use App\Services\JoobleService;
use App\Services\TecnoempleoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FetchTechnologyJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 5;
    public int $timeout = 300;

    public function __construct(
        private readonly string $source,
        private readonly string $technology,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(ProcessJobOfferAction $processJobOffer): void
    {
        Log::info("[{$this->source}] Starting job for: {$this->technology}");

        $service      = $this->resolveService();
        $technologies = Technology::all()->keyBy(function ($technology) {
            return strtolower($technology->name);
        });

        $rawOffers = $service->fetchAll($this->technology);

        Log::info("[{$this->source}] Fetched " . count($rawOffers) . " raw offers for: {$this->technology}");

        foreach ($rawOffers as $item) {
            $processJobOffer->handle($item, $service, $technologies);
        }

        Log::info("[{$this->source}] Done processing: {$this->technology}");
    }

    private function resolveService(): JobSourceInterface
    {
        return match ($this->source) {
            'adzuna'      => app(AdzunaService::class),
            'jooble'      => app(JoobleService::class),
            'tecnoempleo' => app(TecnoempleoService::class),
            default       => throw new \InvalidArgumentException("Unknown source: {$this->source}"),
        };
    }
}
