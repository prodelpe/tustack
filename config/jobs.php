<?php

return [

    /*
     * Job boards republish the same vacancy with a new url every few days. An
     * offer with the same company, title and source seen inside this window is
     * treated as that same vacancy instead of a new one.
     */
    'duplicate_window_days' => 60,

    /*
     * The same vacancy is often listed on several boards at once. A shorter
     * window is used here: across sources there is no republishing to allow
     * for, only the same advert seen twice.
     */
    'cross_source_window_days' => 30,

    /*
     * Adzuna drops the accents from its titles before we ever see them, so a
     * title from a source higher on this list is preferred when merging.
     */
    'title_source_priority' => ['jooble', 'tecnoempleo', 'adzuna'],

    'salary_floor' => 10000,

    'salary_ceiling' => 300000,

];
