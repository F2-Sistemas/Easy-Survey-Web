<?php

return [
    'base_url' => env('API_BASE_URL', 'http://localhost:8000/api'),

    'headers' => [
        'X-Client-ID' => env('API_CLIENT_ID', 'Easy Survey WEB'),
        'X-Client-Env' => env('API_CLIENT_ENV', 'X-CLIENT-ENV'),
        'X-Client-Key' => env('API_CLIENT_KEY', 'X-CLIENT-KEY'),
        'Origin' => env('APP_URL'),
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
];
