<?php

namespace App\Actions;

use App\Models\Technology;
use Illuminate\Support\Collection;

class DetectTechnologiesAction
{
    public function handle(?string $title, ?string $description, Collection $technologies): Collection
    {
        $title = $this->readable($title);
        $text  = trim($title . ' ' . $this->readable($description));

        return $technologies->filter(
            fn (Technology $tech) => $this->detects($tech, $text, $title, $technologies)
        );
    }

    /**
     * Offers arrive as html, and boards write their dashes with whatever the
     * copy and paste brought: "go&nbsp;<b>‑</b>to&#8209;market" has to read as
     * "go-to-market" or no phrase would ever be recognised in it.
     *
     * A tag becomes a space rather than nothing. Dropping it outright glues the
     * items of a list into "awsdocker", and both technologies are lost.
     */
    private function readable(?string $html): string
    {
        $text = preg_replace('/<[^>]*>/', ' ', (string) $html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
        $text = str_replace(['‑', '‐', '–', '—', '−'], '-', $text);
        $text = str_replace("\xC2\xA0", ' ', $text);

        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $text)));
    }

    private function detects(Technology $tech, string $text, string $title, Collection $technologies): bool
    {
        // An alias is spelled out on purpose: golang, expressjs, swiftui. Only
        // the bare name of an ambiguous technology needs vouching for.
        foreach ($tech->aliases ?? [] as $alias) {
            if ($this->appears($text, $alias)) {
                return true;
            }
        }

        $name = mb_strtolower($tech->name);

        if (! $tech->ambiguous) {
            return $this->appears($text, $name);
        }

        return $this->appearsAsTechnology($tech, $name, $text, $title, $technologies);
    }

    private function appears(string $text, string $term): bool
    {
        return (bool) preg_match($this->pattern($term), $text);
    }

    private function pattern(string $term): string
    {
        $term     = mb_strtolower($term);
        $quoted   = preg_quote($term, '/');
        $leading  = ctype_alpha($term[0]) ? '\b' : '';
        $trailing = ctype_alnum($term[-1]) ? '\b' : '';

        return '/' . $leading . $quoted . $trailing . '/i';
    }

    /**
     * "Go" reads in a job ad far more often as go-to-market than as the
     * language, so on its own it proves nothing. It counts when it is in the
     * title, or when something technical stands beside it.
     */
    private function appearsAsTechnology(
        Technology $tech,
        string $name,
        string $text,
        string $title,
        Collection $technologies,
    ): bool {
        $blocked = $this->blockedRanges($tech, $text);

        preg_match_all($this->pattern($name), $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$match, $offset]) {
            if ($this->isBlocked($offset, $blocked)) {
                continue;
            }

            if ($offset < strlen($title) && $this->readsAsATrade($title)) {
                return true;
            }

            if ($this->hasTechnicalNeighbour($text, $offset, strlen($match), $tech, $technologies)) {
                return true;
            }
        }

        return false;
    }

    /** A title naming the trade is talking about the language, not about a game. */
    private function readsAsATrade(string $title): bool
    {
        foreach (config('technologies.title_context_terms') as $term) {
            if ($this->appears($title, $term)) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, array{0: int, 1: int}> */
    private function blockedRanges(Technology $tech, string $text): array
    {
        $ranges = [];

        // Whole words, or "go back" would silence "Go Backend Engineer".
        foreach (config('technologies.blocked_phrases.' . $tech->slug, []) as $phrase) {
            preg_match_all($this->pattern($phrase), $text, $found, PREG_OFFSET_CAPTURE);

            foreach ($found[0] as [$match, $position]) {
                $ranges[] = [$position, $position + strlen($match)];
            }
        }

        return $ranges;
    }

    private function isBlocked(int $offset, array $ranges): bool
    {
        foreach ($ranges as [$start, $end]) {
            if ($offset >= $start && $offset < $end) {
                return true;
            }
        }

        return false;
    }

    private function hasTechnicalNeighbour(
        string $text,
        int $offset,
        int $length,
        Technology $tech,
        Collection $technologies,
    ): bool {
        $window = config('technologies.context_window');
        $start  = max(0, $offset - $window);
        $around = substr($text, $start, $offset - $start + $length + $window);

        foreach ($technologies as $other) {
            if ($other->id === $tech->id) {
                continue;
            }

            foreach (array_merge([$other->name], $other->aliases ?? []) as $term) {
                if ($this->appears($around, $term)) {
                    return true;
                }
            }
        }

        foreach (config('technologies.context_terms') as $term) {
            if ($this->appears($around, $term)) {
                return true;
            }
        }

        return false;
    }
}
