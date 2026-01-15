<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'upload', 'chunk-upload'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:5173',
        'https://your-frontend-domain.com', // Replace with your actual frontend domain
        'https://*.onrender.com' // For Render deployment
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Content-Type',
        'X-Auth-Token',
        'Origin',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Socket-Id',
        'Access-Control-Allow-Origin',
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Port',
        'X-Forwarded-Proto',
        'X-Original-Host',
        'X-Original-Port',
        'X-Original-Proto'
    ],
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
