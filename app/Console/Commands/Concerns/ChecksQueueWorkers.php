<?php

namespace App\Console\Commands\Concerns;

trait ChecksQueueWorkers
{
    private function hasQueueWorkers(): bool
    {
        $output = shell_exec('ps aux | grep -E "[q]ueue:work|[h]orizon"');

        return ! empty(trim($output ?? ''));
    }
}
