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

    'media' => [
        'jellyfin' => [
            'url' => env('JELLYFIN_URL'),
            'key' => env('JELLYFIN_API_KEY'),
            'user_id' => env('JELLYFIN_USER_ID'),
        ],
        'seerr' => [
            'url' => env('SEERR_URL'),
            'key' => env('SEERR_API_KEY'),
        ],
        'radarr' => [
            'url' => env('RADARR_URL'),
            'key' => env('RADARR_API_KEY'),
        ],
        'sonarr' => [
            'url' => env('SONARR_URL'),
            'key' => env('SONARR_API_KEY'),
        ],
        'bazarr' => [
            'url' => env('BAZARR_URL'),
            'key' => env('BAZARR_API_KEY'),
        ],
        'transmission' => [
            'url' => env('TRANSMISSION_URL'),
            'rpc_path' => env('TRANSMISSION_RPC_PATH', '/transmission/rpc'),
            'username' => env('TRANSMISSION_USERNAME'),
            'password' => env('TRANSMISSION_PASSWORD'),
        ],
    ],

];
