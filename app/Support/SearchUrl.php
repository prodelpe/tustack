<?php

namespace App\Support;

class SearchUrl
{
    /**
     * Link to the home search with filters already applied.
     *
     * Brackets are left unencoded on purpose: InstantSearch writes the url
     * that way from the browser, so encoding them here would make the same
     * search look like two different urls.
     */
    public static function withFilters(array $technologies = [], array $provinces = []): string
    {
        $parameters = [];

        foreach (array_values($technologies) as $index => $technology) {
            $parameters["tech[{$index}]"] = $technology;
        }

        foreach (array_values($provinces) as $index => $province) {
            $parameters["prov[{$index}]"] = $province;
        }

        // The separator is passed explicitly because arg_separator.output can be
        // set to &amp; on some setups, which would leak html escaping into the url.
        $query = http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

        return route('home') . '?' . str_replace(['%5B', '%5D'], ['[', ']'], $query);
    }
}
