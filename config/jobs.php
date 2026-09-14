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

    /*
     * Offers are never deleted, so the stored total says more about how long a
     * company has been in the catalogue than about whether it is hiring. This
     * is the window that counts as hiring now. Same as the trends page.
     */
    'active_offer_months' => 12,

    /*
     * Names that are not employers. Boards resell each other, so aggregators
     * arrive as companies, and Jooble files unrelated ads under a name that
     * has nothing to do with them: Tamarind Intelligence is a tobacco
     * regulation research firm that arrived with 296 engineering vacancies in
     * twelve days, and Elpuertodesantamaria is a town. Compared without case,
     * accents or punctuation.
     */
    'blacklisted_companies' => [
        'jobleads',
        'jobtome',
        'jobtailor',
        'domestiko.com',
        'tamarind intelligence',
        'elpuertodesantamaria',
    ],

    /*
     * How long jobs:fetch waits for its batch before giving up and raising the
     * alarm. A full monthly pass takes a few hours; a night that never ends
     * almost always means the queue workers are down.
     */
    'fetch_timeout_hours' => 8,

    'salary_floor' => 10000,

    'salary_ceiling' => 300000,

];
