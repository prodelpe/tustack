<?php

namespace App\Console\Commands;

use App\Jobs\SendSearchAlertJob;
use App\Models\SavedSearch;
use Illuminate\Console\Command;

class SendSearchAlerts extends Command
{
    protected $signature = 'searches:notify';

    protected $description = 'Dispatch alert jobs for saved searches with new matching companies';

    public function handle(): int
    {
        $searches = SavedSearch::with('user')
            ->whereHas('user', fn ($q) => $q->where('alerts_enabled', true))
            ->get();

        if ($searches->isEmpty()) {
            $this->info('No active saved searches.');
            return self::SUCCESS;
        }

        foreach ($searches as $savedSearch) {
            dispatch(new SendSearchAlertJob($savedSearch));
        }

        $this->info("Dispatched {$searches->count()} alert job(s).");

        return self::SUCCESS;
    }
}
