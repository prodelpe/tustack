<?php

namespace App\Console\Commands;

use App\Support\Robots;
use Illuminate\Console\Command;

/**
 * Writes public/robots.txt so the web server can serve it natively.
 *
 * Nginx templates commonly carry `location = /robots.txt { ... }` without
 * passing the path to PHP. When the file is missing, that block 404s and the
 * error page hands the request to the application: the body comes out right and
 * the status stays 404, which crawlers read as "no restrictions at all". A real
 * file sidesteps every server configuration.
 *
 * Run it on deploy and after changing APP_NOINDEX or APP_AVAILABLE.
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
