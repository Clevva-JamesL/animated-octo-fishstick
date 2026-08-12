<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Twitch Extension credentials
    |--------------------------------------------------------------------------
    |
    | Extension Client ID and the shared secret from the Twitch Developer
    | Console. The secret is stored base64-encoded by Twitch; decode before
    | verifying JWTs.
    |
    */
    'extension_client_id' => env('TWITCH_EXTENSION_CLIENT_ID'),

    'extension_secret' => env('TWITCH_EXTENSION_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Local development bypass
    |--------------------------------------------------------------------------
    |
    | When true (local only), requests may send X-Twitch-Dev-Channel and
    | X-Twitch-Dev-Role instead of a real Extension JWT. Never enable in
    | production.
    |
    */
    'allow_dev_auth' => (bool) env('TWITCH_ALLOW_DEV_AUTH', false),
];
