<?php

namespace App\Jobs;

use App\Models\Technology;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchSourceJob implements ShouldQueue
{
    use Queueable;

    public function __construct(private string $source) {}

    public function handle(): void
    {
        $technologies = Technology::orderBy('name')->pluck('name');

        $technologies->each(function (string $technology) {
            dispatch(new FetchTechnologyJob($this->source, $technology));
        });
    }
}
