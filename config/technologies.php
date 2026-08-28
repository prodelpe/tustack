<?php

return [

    /*
     * How far around an ambiguous name we look for something technical. Wide
     * enough to reach the other end of a stack list ("c/c++, python, go,
     * java"), short enough not to reach the next paragraph.
     */
    'context_window' => 110,

    /*
     * Where an ambiguous name certainly is not the technology, keyed by slug.
     * Every one of these was read in a real offer: go-to-market and go-live in
     * management ads, American Express and a lift company called Express, the
     * banking network and the singer for Swift.
     */
    'blocked_phrases' => [

        'go' => [
            'go-to', 'go to', 'go live', 'go-live', 'on the go', 'go hand in hand',
            'go beyond', 'go further', 'go the extra mile', 'ready to go', 'go ahead',
            'go public', 'go global', 'go back', 'go through', 'go out', 'go home',
            'here we go', 'go for it', 'let it go', 'go over', 'go into', 'go above',
            'monopoly go', 'criteo go', 'go dutch', 'go growth',
        ],

        'express' => [
            'american express', 'express yourself', 'express themselves',
            'express your', 'express delivery', 'express interest', 'express train',
            'express - ', 'expressly',
        ],

        'swift' => [
            'taylor swift', 'swift code', 'swift csa', 'swift message',
            'swift network', 'iberclear', 'swift/', '/swift ', 'swift bic',
            'swift and', 'swift response', 'swift action',
        ],

    ],

    /*
     * A title is a handful of words, so a technology list rarely fits in it.
     * What does fit is the trade, and "Desarrollador Go" or "Go Backend
     * Engineer" is the language while "2D Artist - Monopoly GO!" is not.
     */
    'title_context_terms' => [
        'developer', 'desarrollador', 'desenvolupador', 'engineer', 'ingeniero',
        'programador', 'programmer', 'backend', 'back-end', 'frontend',
        'front-end', 'fullstack', 'full-stack', 'architect', 'arquitecto',
        'devops', 'sre', 'software', 'tech lead', 'techlead',
    ],

    /*
     * Not technologies of their own, but they only turn up beside one, so they
     * vouch for an ambiguous name: "ios, swift, objective-c".
     */
    'context_terms' => [
        'ios', 'android', 'xcode', 'objective-c', 'cocoa', 'app store',
        'backend', 'back-end', 'frontend', 'front-end', 'fullstack', 'full-stack',
        'devops', 'microservices', 'microservicios', 'api', 'rest api', 'sdk',
        'framework', 'frameworks', 'stack', 'programming', 'lenguaje', 'lenguajes',
        'compilador', 'runtime', 'open source', 'git', 'ci/cd',
    ],

];
