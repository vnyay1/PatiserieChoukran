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

    // Paiement mobile (Orange Money, MTN MoMo) : https://developer.notchpay.co
    // Sans clé publique, les commandes mobile money restent à confirmer à la main par le vendeur.
    'notchpay' => [
        'base_url' => rtrim((string) env('NOTCHPAY_BASE_URL', 'https://api.notchpay.co'), '/'),
        'public_key' => env('NOTCHPAY_PUBLIC_KEY'),
        // Secret du webhook (tableau de bord NotchPay) : vérifie l'en-tête X-Notch-Signature
        'webhook_hash' => env('NOTCHPAY_WEBHOOK_HASH'),
        // Page du SPA où NotchPay renvoie le client après paiement (?reference=... ajouté)
        'callback_url' => env('NOTCHPAY_CALLBACK_URL'),
        'currency' => env('NOTCHPAY_CURRENCY', 'XAF'),
        'timeout' => (int) env('NOTCHPAY_TIMEOUT', 15),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
