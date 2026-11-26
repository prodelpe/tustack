<?php

namespace App\Console\Commands\Concerns;

trait ChecksQueueWorkers
{
    private function hasQueueWorkers(): bool
    {
        // ww: without a terminal ps cuts every line at 80 columns, which is
        // before the word horizon, and a run from cron never saw the workers.
        $output = shell_exec('ps axww -o command= | grep -E "[q]ueue:work|[h]orizon"');

        return ! empty(trim($output ?? ''));
    }
}
