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
    'gemini' => [
        'api_key' => config('gemini.api_key'),
        'model' => env('GEMINI_MODEL', 'gemini-3.5-flash'), // تغيير النموذج الافتراضي
        'models' => [
            'flash' => 'gemini-3.5-flash',
            'flash_image' => 'gemini-3.1-flash-image', // احتفظ به كخيار احتياطي
            'pro' => 'gemini-2.0-pro',
            'flash_image_2' => 'gemini-2.5-flash-image',
        ],
    ],
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],

];
