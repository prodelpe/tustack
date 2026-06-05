<?php

return [

    'tecnoempleo' => [
        'source'             => 'tecnoempleo',
        'url_pattern'        => 'https://www.tecnoempleo.com/ofertas-trabajo/{query}',
        'location_param'     => 'provincia',
        'pagination_param'   => 'pagina',
        'url_prefix'         => null,
        'selector_next_page' => 'a[rel="next"]',

        'selector_offer'       => 'div.border.rounded.mb-3.bg-white',
        'selector_title'       => 'h3 a',
        'selector_company'     => 'a.link-muted',
        'selector_location'    => 'div.col-12.col-lg-3 b',
        'selector_description' => 'span.hidden-md-down',
        'selector_date'        => 'div.col-12.col-lg-3',

        'date_regex'  => '/(\d{2}\/\d{2}\/\d{4})/',
        'date_format' => 'd/m/Y',
    ],

    'jobatus' => [
        'source'             => 'jobatus',
        'url_pattern'        => 'https://www.jobatus.es/trabajo?q={query}&jb=all',
        'location_param'     => 'l',
        'pagination_param'   => 'p',
        'url_prefix'         => 'https://www.jobatus.es',
        'selector_next_page' => 'a[rel="next"]',

        'selector_offer'       => 'div.result.mt-2',
        'selector_title'       => 'p.jobtitle a.out',
        'selector_company'     => 'span.company span',
        'selector_location'    => 'span.location',
        'selector_description' => 'div.snippet p',
        'selector_date'        => null,

        'date_regex'  => null,
        'date_format' => null,
    ],

];
