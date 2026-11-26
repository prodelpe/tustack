<?php

return [
    'bots' => [
        'tustack_bot' => [
            'token'       => env('TELEGRAM_BOT_TOKEN'),
            'username'    => env('TELEGRAM_BOT_USERNAME'),
            // Telegram only accepts public HTTPS urls, so this stays empty locally.
            'webhook_url' => env('TELEGRAM_BOT_WEBHOOK_URL'),
            // Optional. When set, the webhook only accepts requests carrying it.
            'webhook_secret' => env('TELEGRAM_BOT_WEBHOOK_SECRET'),
        ],
    ],

    'default' => 'tustack_bot',

    'async_requests'  => false,
    'http_client_handler' => null,
];
