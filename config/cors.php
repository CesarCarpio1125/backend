<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar las opciones de CORS para tu aplicación.
    |
    */

    'paths' => ['*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [
        'Authorization',
        'X-CSRF-TOKEN',
        'X-Requested-With',
        'Content-Type',
        'Accept',
        'Origin',
        'Cache-Control',
        'Content-Language',
        'Expires',
        'Last-Modified',
        'Pragma',
        'X-XSRF-TOKEN'
    ],
    'max_age' => 0,
    'supports_credentials' => true,
];
