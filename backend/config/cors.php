<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ))),

    'allowed_origins_patterns' => [
        '#^https://[\\w-]+\\.ext-twitch\\.tv$#',
        '#^https://([\\w-]+\\.)?twitch\\.tv$#',
        '#^https://localhost(:\\d+)?$#',
        '#^http://localhost(:\\d+)?$#',
        '#^https://[\\w.-]+\\.ngrok-free\\.app$#',
        '#^https://[\\w.-]+\\.ngrok\\.io$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
