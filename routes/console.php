<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Enable these on the day of the production deploy, never before: the
// enrichment spends real money and there is no point running it while the
// fetch is off. --limit keeps the nightly cost around six cents.
//
// The nightly fetch only asks for the last few days, which is some five times
// less work. The margin covers a night the cron failed or a board publishing
// late. Once a month everything is asked for again, so nothing stays missing
// for long and offers that changed at the source are picked up.
// Schedule::command('jobs:fetch --all --since=4')->dailyAt('00:00');
// Schedule::command('jobs:fetch --all')->monthlyOn(1, '00:00');
// Schedule::command('companies:enrich --limit=50')->dailyAt('03:00');

Schedule::command('companies:resolve-provinces')->dailyAt('01:30');
Schedule::command('searches:notify')->dailyAt('08:00');
