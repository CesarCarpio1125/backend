<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'upload', 'chunk-upload', 'send-email'],
    'allowed_methods' => ['*'],
    'allowed_origins' => ['*'], // Allow all origins for now, you can restrict this in production
    'allowed_origins_patterns' => [
        'http://localhost:[0-9]+',
        'https://.+\.onrender\.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
        'X-Requested-With'
    ],
    'max_age' => 60 * 60 * 24,
    'supports_credentials' => true,
];
