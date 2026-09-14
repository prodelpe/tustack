<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * A board that fails is caught inside the job so the other boards still run,
 * which also hides the failure from the batch: an expired Adzuna key would
 * leave every query "successful". The failures are counted here, per batch and
 * per board, where the command can read them once the batch is done.
 */
class FetchRun
{
    public const SOURCES = ['adzuna', 'jooble', 'tecnoempleo'];

    public static function recordSourceFailure(string $batchId, string $source): void
    {
        $key = self::key($batchId, $source);

        // add() first: not every cache store creates a missing key on increment.
        Cache::add($key, 0, now()->addDays(2));
        Cache::increment($key);
    }

    /** @return array<string, int> failures per board, only the boards that failed */
    public static function sourceFailures(string $batchId): array
    {
        $failures = [];

        foreach (self::SOURCES as $source) {
            $count = (int) Cache::get(self::key($batchId, $source), 0);

            if ($count > 0) {
                $failures[$source] = $count;
            }
        }

        return $failures;
    }

    private static function key(string $batchId, string $source): string
    {
        return "fetch:{$batchId}:failures:{$source}";
    }
}
