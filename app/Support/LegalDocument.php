<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

/**
 * Legal texts live as markdown, one file per document and language, so they can
 * be read and corrected by whoever has to sign them without touching code.
 */
class LegalDocument
{
    public const DOCUMENTS = ['legal_notice', 'privacy', 'cookies'];

    public static function isKnown(string $document): bool
    {
        return in_array($document, self::DOCUMENTS, strict: true)
            && file_exists(self::path($document));
    }

    public static function html(string $document): string
    {
        return Str::markdown(file_get_contents(self::path($document)));
    }

    public static function updatedAt(string $document): Carbon
    {
        return Carbon::createFromTimestamp(filemtime(self::path($document)));
    }

    private static function path(string $document, ?string $locale = null): string
    {
        $locale ??= App::getLocale();
        $path = resource_path("legal/{$locale}/{$document}.md");

        return file_exists($path)
            ? $path
            : resource_path('legal/' . config('app.fallback_locale') . "/{$document}.md");
    }
}
