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

class FetchTechnologyJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        private string $source,
        private string $technology,
    ) {}

    public function backoff(): array
    {
        return [60, 300, 900, 1800, 3600];
    }

    public function handle(ProcessJobOfferAction $processJobOffer): void
    {
        $service      = $this->resolveService();
        $technologies = Technology::all()->keyBy(function ($technology) {
            return strtolower($technology->name);
        });

        $rawOffers = $service->fetchAll($this->technology);

        foreach ($rawOffers as $item) {
            $processJobOffer->handle($item, $service, $technologies);
        }
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
