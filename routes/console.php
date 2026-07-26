<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Enable both of these on the day of the production deploy, never before:
// the enrichment spends real money and there is no point running it while
// the fetch is off. --limit keeps the nightly cost around six cents.
// Schedule::command('jobs:fetch --all')->dailyAt('00:00');
// Schedule::command('companies:enrich --limit=50')->dailyAt('03:00');

// Schedule::job(new App\Jobs\FetchSourceJob('adzuna'))->dailyAt('00:00');
// Schedule::job(new App\Jobs\FetchSourceJob('jooble'))->dailyAt('00:00'); // disabled: API quota exhausted
// Schedule::job(new App\Jobs\FetchSourceJob('tecnoempleo'))->dailyAt('00:00');
Schedule::command('searches:notify')->dailyAt('08:00');
