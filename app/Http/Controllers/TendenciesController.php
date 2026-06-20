<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use Throwable;

class TendenciesController extends Controller
{
    public function __invoke()
    {
        $host = config('scout.meilisearch.host');

        try {
            $healthy = Http::timeout(2)->get("{$host}/health")->successful();
        } catch (Throwable) {
            $healthy = false;
        }

        return view('tendencies', [
            'meilisearchHost'      => $host,
            'meilisearchKey'       => config('scout.meilisearch.key'),
            'meilisearchAvailable' => $healthy,
        ]);
    }
}
