<?php

namespace App\Support;

/**
 * One switch in front of every paid call. Commented schedules protect against
 * the cron and nothing else; this also protects against a command typed by
 * hand and against whatever calls Gemini next, because a new caller has to go
 * through here to reach the api.
 */
class Gemini
{
    public static function isEnabled(): bool
    {
        return (bool) config('services.gemini.enabled')
            && filled(config('services.gemini.api_key'));
    }

    /** Why nothing happened, in words worth printing in a terminal. */
    public static function whyItIsOff(): string
    {
        if (! config('services.gemini.enabled')) {
            return 'Gemini is switched off. Set GEMINI_ENABLED=true in .env to allow paid calls.';
        }

        return 'Gemini has no api key: set GEMINI_API_KEY in .env.';
    }
}
