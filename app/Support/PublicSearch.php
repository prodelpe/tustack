<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
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

    /**
     * Asked of the address the browser is handed, not of the one the server
     * happens to reach. A vhost pointing at the wrong port answers perfectly
     * well from inside the machine while every visitor gets nothing, and the
     * page went on claiming all was well for a month.
     *
     * Cached, because from production this is now a round trip out of the
     * server and back, and it runs on every render of the home, map and trends
     * pages. A minute is short enough that an outage is noticed and long enough
     * that the pages do not pay for it.
     */
    private static function isAvailable(): bool
    {
        return Cache::remember('search.available', now()->addMinute(), function () {
            $host = rtrim((string) config('search.host'), '/');

            if ($host === '') {
                return false;
            }

            try {
                return Http::timeout(3)->get($host . '/health')->successful();
            } catch (Throwable) {
                return false;
            }
        });
    }
}
