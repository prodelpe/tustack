<?php

namespace App\Support;

use App\Models\Province;
use App\Models\Technology;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Landing page urls pack two values into one segment
 * (empresas-spring-boot-santa-cruz-de-tenerife), and both of them can contain
 * dashes. Constraining each parameter to the slugs that actually exist is what
 * makes the split unambiguous.
 *
 * Run `php artisan cache:clear` after adding a technology, or the new slug
 * will not be recognised by the router.
 */
class SeoRoutePatterns
{
    private const FALLBACK = '[a-z0-9-]+';

    public static function technologies(): string
    {
        return self::pattern('routes.technology-slugs', Technology::class);
    }

    public static function provinces(): string
    {
        return self::pattern('routes.province-slugs', Province::class);
    }

    /**
     * @param class-string<Model> $model
     */
    private static function pattern(string $key, string $model): string
    {
        return rescue(function () use ($key, $model) {
            return Cache::rememberForever($key, function () use ($model) {
                $slugs = $model::query()->whereNotNull('slug')->pluck('slug');

                return $slugs->isEmpty()
                    ? self::FALLBACK
                    : $slugs->map(fn (string $slug) => preg_quote($slug, '/'))->implode('|');
            });
        }, self::FALLBACK, false);
    }
}
