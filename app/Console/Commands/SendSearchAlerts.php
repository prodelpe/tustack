<?php

namespace App\Console\Commands;

use App\Jobs\SendSearchAlertJob;
use App\Models\CommandLog;
use App\Models\SavedSearch;
use Illuminate\Console\Command;
use Throwable;

class SendSearchAlerts extends Command
{
    protected $signature = 'searches:notify';

    protected $description = 'Dispatch alert jobs for saved searches with new matching companies';

    public function handle(): int
    {
        $log = CommandLog::create([
            'command'    => 'searches:notify',
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $searches = SavedSearch::with('user')
                ->whereHas('user', fn ($q) => $q->where('alerts_enabled', true))
                ->get();

            if ($searches->isEmpty()) {
                $this->info('No active saved searches.');

                $log->update([
                    'status'      => 'success',
                    'finished_at' => now(),
                    'stats'       => ['dispatched' => 0],
                ]);

                return self::SUCCESS;
            }

            foreach ($searches as $savedSearch) {
                dispatch(new SendSearchAlertJob($savedSearch));
            }

            $this->info("Dispatched {$searches->count()} alert job(s).");

            $log->update([
                'status'      => 'success',
                'finished_at' => now(),
                'stats'       => ['dispatched' => $searches->count()],
            ]);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'finished_at'   => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
