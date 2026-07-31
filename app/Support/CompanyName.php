<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Company names come from job boards exactly as whoever typed the offer wrote
 * them, so a fair share arrive as "knowmad mood" or "CAPITOLE CONSULTING".
 * This only touches names written entirely in one case: anything with
 * deliberate capitals (eBay, iSalud, Grupo Oesía) is left alone.
 */
class CompanyName
{
    /** Kept lowercase unless they open the name. */
    private const PARTICLES = ['de', 'del', 'la', 'las', 'el', 'los', 'y', 'e', 'i', 'en', 'para', 'por', 'con', 'a', 'of', 'and', 'the'];

    /** Legal forms and the like, always uppercase. */
    private const UPPERCASE = ['sl', 's.l', 's.l.', 'slu', 's.l.u', 'sa', 's.a', 's.a.', 'sau', 'scp', 'sll', 'ute', 'aie', 'llc', 'ltd', 'inc', 'bv', 'nv', 'gmbh', 'ag', 'srl', 'spa', 'plc', 'it', 'ti'];

    /**
     * Dropped when matching: boards write them as they please, or not at all.
     * "spa" is deliberately absent, it is a word of its own around here.
     */
    private const LEGAL_FORMS = ['slu', 'sl', 'sau', 'sa', 'sll', 'scp', 'ute', 'aie', 'llc', 'ltd', 'inc', 'bv', 'nv', 'gmbh', 'ag', 'srl', 'plc'];

    /**
     * The comparable form of a name: no case, no accents, no punctuation and no
     * legal form, so "Sopra-Steria" and "Sopra Steria, S.L." meet. Never shown.
     */
    public static function normalize(?string $name): ?string
    {
        if (blank($name)) {
            return null;
        }

        $words = self::words($name);

        return self::withoutLegalForms($words)
            ?: str_replace(' ', '', $words)
            ?: mb_strtolower(trim($name));
    }

    private static function words(string $name): string
    {
        $name = str_replace('.', '', Str::ascii(mb_strtolower(trim($name))));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $name));
    }

    private static function withoutLegalForms(string $words): string
    {
        $pattern = '/\b(' . implode('|', self::LEGAL_FORMS) . ')\b/';

        return str_replace(' ', '', preg_replace($pattern, ' ', $words));
    }

    public static function display(string $name): string
    {
        $name = trim($name);

        if ($name === '' || ! self::isSingleCase($name)) {
            return $name;
        }

        return self::titleCase($name);
    }

    /**
     * True when the name has letters and they are either all lower or all upper.
     * All-caps names of a single short word are excluded: they are acronyms
     * (IBM, SAP), and title casing them would produce "Ibm".
     */
    private static function isSingleCase(string $name): bool
    {
        if (! preg_match('/\pL/u', $name)) {
            return false;
        }

        if (mb_strtolower($name) === $name) {
            return true;
        }

        if (mb_strtoupper($name) !== $name) {
            return false;
        }

        $words = preg_split('/[\s,]+/u', $name, flags: PREG_SPLIT_NO_EMPTY);

        // Every word long enough not to look like an acronym.
        return count($words) > 1 && collect($words)->every(
            fn (string $word) => mb_strlen(preg_replace('/[^\pL]/u', '', $word)) >= 4
        );
    }

    private static function titleCase(string $name): string
    {
        $words = preg_split('/(\s+)/u', mb_strtolower($name), flags: PREG_SPLIT_DELIM_CAPTURE);
        $result = '';
        $index  = 0;

        foreach ($words as $word) {
            if (trim($word) === '') {
                $result .= $word;

                continue;
            }

            $bare = rtrim($word, ',.');

            $result .= match (true) {
                in_array($bare, self::UPPERCASE, strict: true) => mb_strtoupper($word),
                $index > 0 && in_array($bare, self::PARTICLES, strict: true) => $word,
                default => Str::ucfirst($word),
            };

            $index++;
        }

        return $result;
    }
}
