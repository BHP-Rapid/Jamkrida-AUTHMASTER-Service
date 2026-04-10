<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'auth' => [
        'url' => env('AUTH_SERVICE_URL', env('APP_URL')),
        'token' => env('AUTH_SERVICE_TOKEN'),
        'timeout' => env('AUTH_SERVICE_TIMEOUT', 10),
    ],

    'user_service' => [
        'base_url' => env('USER_SERVICE_BASE_URL', 'http://localhost:8001/api'),
        'token' => env('USER_SERVICE_TOKEN'),
        'timeout' => env('USER_SERVICE_TIMEOUT', 10),
    ],

    'internal' => [
        'token' => env('INTERNAL_SERVICE_TOKEN'),
    ],

];
