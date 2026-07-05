<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule::job(new App\Jobs\FetchSourceJob('adzuna'))->dailyAt('00:00');
// Schedule::job(new App\Jobs\FetchSourceJob('jooble'))->dailyAt('00:00'); // disabled: API quota exhausted
// Schedule::job(new App\Jobs\FetchSourceJob('tecnoempleo'))->dailyAt('00:00');
Schedule::command('searches:notify')->dailyAt('08:00');
