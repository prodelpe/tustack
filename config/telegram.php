<?php

return [
    'bots' => [
        'tustack_bot' => [
            'token'    => env('TELEGRAM_BOT_TOKEN'),
            'username' => env('TELEGRAM_BOT_USERNAME'),
        ],
    ],

    'default' => 'tustack_bot',

    'async_requests'  => false,
    'http_client_handler' => null,
];
