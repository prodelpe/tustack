<?php

return [

    // A landing page only exists when it has at least this many companies.
    // Thin pages built out of one or two results are what search engines
    // call doorway pages, and they penalise the whole domain for them.
    'minimum_companies' => 5,

    // Companies listed on a landing page before the list is cut off.
    'companies_per_page' => 100,

];
