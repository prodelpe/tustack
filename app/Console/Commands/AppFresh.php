<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppFresh extends Command
{
    protected $signature = 'app:fresh
                            {--restore : Restore the enrichment snapshot after fetching}
                            {--enrich : Run companies:enrich after setup}';

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

        if (! $this->hasQueueWorkers()) {
            $this->error('No queue workers detected. Start them first:');
            $this->line('  php artisan queue:work');
            return self::FAILURE;
        }

        $this->call('migrate:fresh');
        $this->call('db:seed');
        $this->call('jobs:fetch', ['--all' => true]);
        $this->call('companies:resolve-provinces');

        if ($this->option('restore')) {
            $this->call('enrichment:import');
        }

        $this->call('scout:sync-index-settings');
        $this->call('scout:import', ['model' => 'App\\Models\\Company']);

        if ($this->option('enrich')) {
            $this->call('companies:enrich');
        } else {
            $this->info('--- Enrichment cost estimate (not running) ---');
            $this->call('companies:enrich', ['--estimate' => true]);
            $this->info('Run <fg=yellow>php artisan companies:enrich</> when ready to enrich.');
        }

        return self::SUCCESS;
    }

    private function hasQueueWorkers(): bool
    {
        $output = shell_exec('ps aux | grep -E "[q]ueue:work|[h]orizon"');
        return ! empty(trim($output ?? ''));
    }
}
