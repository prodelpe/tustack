<?php

return [

    /*
     * Job boards republish the same vacancy with a new url every few days. An
     * offer with the same company, title and source seen inside this window is
     * treated as that same vacancy instead of a new one.
     */
    'duplicate_window_days' => 60,

    'salary_floor' => 10000,

    'salary_ceiling' => 300000,

];
