<?php

return [

    /*
     * Measuring also needs the production environment, so a copy of the site
     * running anywhere else never sends visits to the real dashboard.
     */
    'enabled' => env('ANALYTICS_ENABLED', false),

    'umami' => [
        'script_url' => env('UMAMI_SCRIPT_URL'),
        'website_id' => env('UMAMI_WEBSITE_ID'),
    ],

];
