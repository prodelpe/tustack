<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Failures are caught inside the job so the rest of the work still runs, which
 * also hides them from the batch: an expired Adzuna key would leave every
 * query "successful". They are counted here, per batch and per board, where
 * the command can read them once the batch is done. Two kinds: a board that
 * could not be asked at all, and a single offer that could not be stored.
 */
class FetchRun
{
    public const SOURCES = ['adzuna', 'jooble', 'tecnoempleo'];

    public static function recordSourceFailure(string $batchId, string $source): void
    {
        self::increment(self::key($batchId, 'failures', $source));
    }

    public static function recordOfferFailure(string $batchId, string $source): void
    {
        self::increment(self::key($batchId, 'offer-failures', $source));
    }

    /** @return array<string, int> failures per board, only the boards that failed */
    public static function sourceFailures(string $batchId): array
    {
        return self::counts($batchId, 'failures');
    }

    /** @return array<string, int> offers that could not be stored, per board */
    public static function offerFailures(string $batchId): array
    {
        return self::counts($batchId, 'offer-failures');
    }

    private static function increment(string $key): void
    {
        // add() first: not every cache store creates a missing key on increment.
        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key);
    }

    private static function counts(string $batchId, string $kind): array
    {
        $counts = [];

        foreach (self::SOURCES as $source) {
            $count = (int) Cache::get(self::key($batchId, $kind, $source), 0);

            if ($count > 0) {
                $counts[$source] = $count;
            }
        }

        return $counts;
    }

    private static function key(string $batchId, string $kind, string $source): string
    {
        return "fetch:{$batchId}:{$kind}:{$source}";
    }
}
