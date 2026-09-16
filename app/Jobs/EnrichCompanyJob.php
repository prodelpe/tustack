<?php

namespace App\Jobs;

use App\Actions\EnrichCompanyWithGeminiAction;
use App\Models\Company;
use App\Support\Gemini;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class EnrichCompanyJob implements ShouldQueue
{
    use Queueable, Batchable;

    public int $timeout = 60;
    public int $tries   = 2;

    public function __construct(
        public readonly int $companyId,
    ) {}

    public function handle(EnrichCompanyWithGeminiAction $action): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        // A worker keeps the configuration it started with, so switching Gemini on
        // without restarting Horizon used to skip every company while the batch
        // reported success: 842 of them on 14 September.
        if (! Gemini::isEnabled()) {
            $this->fail(new RuntimeException(Gemini::whyItIsOff() . ' If it is already on in .env, restart Horizon: php artisan horizon:terminate'));

            return;
        }

        $company = Company::with(['jobOffers.technologies', 'province'])->find($this->companyId);

        if (! $company) {
            return;
        }

        try {
            $action->handle($company);
        } catch (Throwable $e) {
            Log::warning('EnrichCompanyJob failed', [
                'company_id' => $this->companyId,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
