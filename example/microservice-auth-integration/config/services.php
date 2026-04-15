<?php

return [
    'auth_internal' => [
        'url' => env('AUTH_SERVICE_URL', 'http://localhost:8000'),
        'token' => env('AUTH_INTERNAL_TOKEN'),
        'timeout' => env('AUTH_INTERNAL_TIMEOUT', 10),
    ],
];
