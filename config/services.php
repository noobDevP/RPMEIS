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

    'pmc' => [
        'url' => env('PMC_API_URL', 'http://127.0.0.1:8002/api'),
        'token' => env('PMC_API_TOKEN', 'philarmy-pme-secure-token-2026'),
    ],

    'pme' => [
        'url' => env('PRODUCTION_IS_URL', 'http://philarmy-recruitment.com'),
        'secret' => env('PME_SYNC_SECRET', 'philarmy-pme-secure-token-2026'),
    ],

    'philarmylab_legacy' => [
        'url' => rtrim((string) env('PHILARMYLAB_LEGACY_URL', 'https://philarmy-lab.com'), '/'),
        'endpoint' => '/' . ltrim((string) env('PHILARMYLAB_LEGACY_ENDPOINT', 'api_sync_export.php'), '/'),
        'secret' => (string) env('PHILARMYLAB_LEGACY_SECRET', ''),
        'webhook_tolerance_seconds' => (int) env('PHILARMYLAB_LEGACY_TOLERANCE', 300),
        'timeout' => (int) env('PHILARMYLAB_LEGACY_TIMEOUT', 20),
    ],

];
