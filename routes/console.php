<?php

use App\Support\Gemini;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// The nightly fetch only asks for the last few days, which is some five times
// less work. The margin covers a night the cron failed or a board publishing
// late. Once a month everything is asked for again, so nothing stays missing
// for long and offers that changed at the source are picked up. The nightly
// run steps aside on the first, or both would start at the same minute.
Schedule::command('jobs:fetch --all --since=4')
    ->dailyAt('00:00')
    ->skip(function () {
        return now()->day === 1;
    });

Schedule::command('jobs:fetch --all')->monthlyOn(1, '00:00');

// Everything that spends money waits for GEMINI_ENABLED, so turning it on is a
// change to .env and never to code. With the switch off these are skipped
// without a word instead of failing every week.
//
// Weekly rather than nightly: the site is an experiment and a week of new
// names is still a small batch. Aliases go first so a duplicate is merged
// before anyone pays to describe it twice. --limit caps a week at about 22
// cents should the switch ever be left on.
Schedule::command('companies:find-aliases')
    ->weeklyOn(0, '02:30')
    ->when(function () {
        return Gemini::isEnabled();
    });

Schedule::command('companies:enrich --limit=200')
    ->weeklyOn(0, '03:00')
    ->when(function () {
        return Gemini::isEnabled();
    });

Schedule::command('companies:resolve-provinces')->dailyAt('01:30');
Schedule::command('searches:notify')->dailyAt('08:00');
