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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'sms' => [
        'enabled' => env('SMS_ENABLED', false),
        'provider' => env('SMS_PROVIDER', 'test'),
        'max_length' => env('SMS_MAX_LENGTH', 160),
        'supports_unicode' => env('SMS_SUPPORTS_UNICODE', true),
        
        // SMSC.ru настройки
        'smsc' => [
            'login' => env('SMSC_LOGIN'),
            'password' => env('SMSC_PASSWORD'),
            'sender' => env('SMSC_SENDER', 'SPA-COM'),
        ],
        
        // SMS.ru настройки
        'smsru' => [
            'api_id' => env('SMSRU_API_ID'),
            'from' => env('SMSRU_FROM', 'SPA-COM'),
        ],
        
        // Twilio настройки
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from' => env('TWILIO_FROM'),
        ],
    ],

];
