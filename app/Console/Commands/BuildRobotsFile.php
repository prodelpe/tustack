<?php

namespace App\Console\Commands;

use App\Support\Robots;
use Illuminate\Console\Command;

/**
 * Nginx serves /robots.txt as a static file without passing it to PHP, and a
 * missing file 404s. Run on deploy and after changing the flags.
 */
class BuildRobotsFile extends Command
{
    protected $signature = 'robots:build';

    protected $description = 'Write public/robots.txt from the current app flags';

    public function handle(): int
    {
        $path = public_path('robots.txt');

        file_put_contents($path, Robots::content());

        $this->info('Written ' . $path);
        $this->line('<fg=gray>' . trim(Robots::content()) . '</>');

        if (config('app.noindex') || ! config('app.available')) {
            $this->line('<fg=yellow>Everything is disallowed: the site is flagged as noindex or unavailable.</>');
        }

        return self::SUCCESS;
    }
}
