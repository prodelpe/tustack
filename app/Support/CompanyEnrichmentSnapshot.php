<?php

namespace App\Support;

use App\Models\Company;

/**
 * Versioned snapshot of the company data that costs money to produce.
 * Entries are keyed by company name because ids change on every migrate:fresh.
 */
class CompanyEnrichmentSnapshot
{
    private const FIELDS = ['sector', 'employees', 'website', 'latitude', 'longitude'];

    public static function path(): string
    {
        return database_path('data/company_enrichment.json.gz');
    }

    public static function exists(): bool
    {
        return file_exists(self::path());
    }

    /** MySQL compares names case insensitively, PHP array keys do not. */
    public static function key(string $name): string
    {
        return mb_strtolower(trim($name));
    }

    public static function read(): array
    {
        if (! self::exists()) {
            return [];
        }

        $contents = gzdecode(file_get_contents(self::path()));

        return json_decode($contents, true) ?? [];
    }

    public static function write(array $entries): void
    {
        $directory = dirname(self::path());

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        ksort($entries, SORT_NATURAL | SORT_FLAG_CASE);

        $json = json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        file_put_contents(self::path(), gzencode($json, 9));
    }

    /** Only non-empty values are written, so the snapshot can grow but never loses data. */
    public static function merge(?array $entry, Company $company): array
    {
        $entry ??= [];

        foreach (self::FIELDS as $field) {
            if (filled($company->{$field})) {
                $entry[$field] = $company->{$field};
            }
        }

        $descriptions = self::supportedDescriptions($company->description);

        if (! empty($descriptions)) {
            $entry['description'] = array_merge($entry['description'] ?? [], $descriptions);
        }

        if ($company->gemini_enriched) {
            $entry['gemini_enriched'] = true;
        }

        return $entry;
    }

    /** Without $force, only empty columns are filled. */
    public static function attributesFor(Company $company, array $entry, bool $force = false): array
    {
        $attributes = [];

        foreach (self::FIELDS as $field) {
            if (! array_key_exists($field, $entry)) {
                continue;
            }

            if ($force || blank($company->{$field})) {
                $attributes[$field] = $entry[$field];
            }
        }

        $descriptions = self::supportedDescriptions($entry['description'] ?? null);

        if (! empty($descriptions)) {
            $current = $force ? [] : ($company->description ?? []);
            $merged  = array_merge($descriptions, array_filter($current));

            if ($merged !== ($company->description ?? [])) {
                $attributes['description'] = $merged;
            }
        }

        if (($entry['gemini_enriched'] ?? false) && ! $company->gemini_enriched) {
            $attributes['gemini_enriched'] = true;
        }

        return $attributes;
    }

    private static function supportedDescriptions(mixed $description): array
    {
        if (! is_array($description)) {
            return [];
        }

        $locales = array_keys(config('laravellocalization.supportedLocales'));

        return array_filter(
            array_intersect_key($description, array_flip($locales)),
            function ($text) {
                return filled($text);
            }
        );
    }
}
