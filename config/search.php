<?php

return [

    // Injected into the page by the InstantSearch widgets, so both values are
    // public. Never point them at the admin key or the internal host.

    // ?: because an empty .env value reaches env() as '', not as a missing key
    'host' => env('MEILISEARCH_PUBLIC_HOST') ?: env('MEILISEARCH_HOST'),

    'key' => env('MEILISEARCH_KEY'),

];
