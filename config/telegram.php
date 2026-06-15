<?php

return [
    'bots' => [
        'findyourdevstack_bot' => [
            'token'    => env('TELEGRAM_BOT_TOKEN'),
            'username' => env('TELEGRAM_BOT_USERNAME'),
        ],
    ],

    'default' => 'findyourdevstack_bot',

    'async_requests'  => false,
    'http_client_handler' => null,
];
