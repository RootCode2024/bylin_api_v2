<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */
    'paths' => [
        'api/*',
        'api/v1/*',
        'sanctum/csrf-cookie',
        'sanctum/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => explode(',', env(
        'CORS_ALLOWED_ORIGINS',
        'http://localhost:3000,http://localhost:3001,http://127.0.0.1:3000'
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'Authorization',
        'Accept',
        'Origin',
        'X-XSRF-TOKEN',
        'X-Session-ID',
    ],

    'exposed_headers' => ['X-XSRF-TOKEN'],

    'max_age' => 0,

    'supports_credentials' => true,
];
