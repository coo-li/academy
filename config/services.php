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

    'asana' => [
        'token' => env('ASANA_ACCESS_TOKEN'),
        'project_id' => env('ASANA_PROJECT_ID'),
    ],

    'google' => [
        'calendar_id' => env('GOOGLE_CALENDAR_ID', 'primary'),
        'calendar_impersonate' => env('GOOGLE_CALENDAR_IMPERSONATE'),
    ],

    'personio' => [
        'client_id' => env('PERSONIO_CLIENT_ID'),
        'client_secret' => env('PERSONIO_CLIENT_SECRET'),
        'base_url' => env('PERSONIO_BASE_URL', 'https://api.personio.de/v1'),
    ],

];
