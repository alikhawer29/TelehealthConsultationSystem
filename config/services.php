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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'firebase' => [
        'server_key' => env('FIREBASE_SERVER_KEY')
    ],
    'stripe' => [
        'secret' => env('STRIPE_SECRET_KEY'),
    ],

    'webex' => [
        'client_id' => env('WEBEX_CLIENT_ID'),
        'client_secret' => env('WEBEX_CLIENT_SECRET'),
        'redirect_uri' => env('WEBEX_REDIRECT_URI'),
        'frontend_uri' => env('FRONTEND_REDIRECT_URI'),
        'guest_issuer_id' => env('WEBEX_GUEST_ISSUER_ID'),
        'guest_secret' => env('WEBEX_GUEST_SECRET'),
    ],

    'zoho' => [
        'client_id' => env('ZOHO_CLIENT_ID'),
        'client_secret' => env('ZOHO_CLIENT_SECRET'),
        'redirect_uri' => env('ZOHO_REDIRECT_URI'),
        'frontend_uri' => env('ZOHO_FRONTEND_REDIRECT_URI'),
        'client_org_id' => env('ZOHO_ORG_CLIENT_ID'),
        'client_org_secret' => env('ZOHO_ORG_CLIENT_SECRET'),
    ],
];
