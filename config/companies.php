<?php

return [

    /*
     * Two names are asked about when one reads inside the other, or when they
     * are this close letter by letter.
     */
    'alias_max_edit_distance' => 2,

    /*
     * Only long names are compared letter by letter. Measured on the catalogue,
     * dropping below ten characters raises two hundred pairs that are all
     * different companies: Adyen and Aderen, Viseo and Cisco, Neoris and Geodis.
     */
    'alias_minimum_length_for_edit_distance' => 10,

    /*
     * Pairs sent to Gemini in a single request. Forty fits comfortably in one
     * answer and keeps a failed request from costing much.
     */
    'alias_batch_size' => 40,

    /*
     * A ceiling on what one run can spend. A new source could raise hundreds of
     * candidates in a night; the rest simply wait for the next run.
     */
    'alias_max_pairs_per_run' => 200,

    /*
     * Names shown to Gemini at once when sweeping the whole catalogue looking
     * for pairs no rule can see, such as Telefónica and Movistar.
     */
    'alias_sweep_block_size' => 60,

];
