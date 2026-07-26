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

    // Свой coturn для голосовых каналов (фолбэк, если Cloudflare TURN ниже не настроен).
    // Секрет должен совпадать со static-auth-secret в /etc/turnserver.conf на сервере с coturn.
    'turn' => [
        'host' => env('TURN_HOST'),
        'secret' => env('TURN_SECRET'),
    ],

    // Metered.ca — основной вариант: 500 МБ TURN-трафика в месяц бесплатно,
    // БЕЗ привязки карты. Регистрация: dashboard.metered.ca/signup ->
    // раздел TURN Servers -> там же appName (поддомен *.metered.live) и API Key.
    'metered_turn' => [
        'app_name' => env('METERED_TURN_APP_NAME'),
        'api_key' => env('METERED_TURN_API_KEY'),
    ],

    // Cloudflare Realtime TURN — опционально, 1000 ГБ/мес бесплатно, но требует
    // привязать карту (не списывает, пока не выйдешь за бесплатный лимит).
    // Взять TURN Token ID и API Token: dash.cloudflare.com -> Calls -> Create TURN App.
    'cloudflare_turn' => [
        'key_id' => env('CLOUDFLARE_TURN_KEY_ID'),
        'token' => env('CLOUDFLARE_TURN_TOKEN'),
    ],

];
