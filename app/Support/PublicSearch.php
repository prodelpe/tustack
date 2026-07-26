<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Throwable;

class PublicSearch
{
    /** Everything the browser needs to query Meilisearch directly. */
    public static function viewData(): array
    {
        return [
            'meilisearchHost'      => config('search.host'),
            'meilisearchKey'       => config('search.key'),
            'meilisearchAvailable' => self::isAvailable(),
        ];
    }

    private static function isAvailable(): bool
    {
        try {
            return Http::timeout(2)->get(config('scout.meilisearch.host') . '/health')->successful();
        } catch (Throwable) {
            return false;
        }
    }
}
