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

    'txtbox' => [
        'url' => env('TXTBOX_URL', 'https://ws-v2.txtbox.com/messaging/v1/sms/push'),
        'api_key' => env('TXTBOX_API_KEY'),
        'timeout' => (int) env('TXTBOX_TIMEOUT_SECONDS', 10),
        'sender' => env('TXTBOX_SENDER'),
        'force_send' => filter_var(env('TXTBOX_FORCE_SEND', false), FILTER_VALIDATE_BOOLEAN),
    ],

];
