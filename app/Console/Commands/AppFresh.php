<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppFresh extends Command
{
    protected $signature = 'app:fresh';

    protected $description = 'Full app setup from scratch: migrate, seed, fetch jobs, index and estimate enrichment cost';

    public function handle(): int
    {
        $this->line('');
        $this->line('<bg=red;fg=white;options=bold> DANGER </> This will drop ALL data and reset the database.');
        $this->line('');

        $confirm = $this->ask('Type <fg=yellow>yes</> to continue');

        if ($confirm !== 'yes') {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $this->call('migrate:fresh');
        $this->call('db:seed');
        $this->call('jobs:fetch', ['--all' => true]);
        $this->call('scout:sync-index-settings');
        $this->call('scout:import', ['model' => 'App\\Models\\Company']);

        $this->info('--- Enrichment cost estimate (not running) ---');
        $this->call('companies:enrich', ['--estimate' => true]);

        $this->info('Done. Run <fg=yellow>php artisan companies:enrich</> when ready to enrich.');

        return self::SUCCESS;
    }
}
