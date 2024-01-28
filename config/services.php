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
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    /*
    |--------------------------------------------------------------------------
    | Hydra
    |--------------------------------------------------------------------------
    */
    'apps' => [
        'admin' => [
            'openid_configuration' => env('IDENTITY_OPENID_CONFIGURATION'),
            'client_id' => env('IDENTITY_ADMIN_ID'),
            'client_secret' => env('IDENTITY_ADMIN_SECRET'),
            'redirect' => env('IDENTITY_ADMIN_CALLBACK_URL'),
            'scopes' => 'openid offline_access email profile groups',
            'home_route' => 'filament.admin.pages.dashboard',
        ],
        'portal' => [
            'openid_configuration' => env('IDENTITY_OPENID_CONFIGURATION'),
            'client_id' => env('IDENTITY_PORTAL_ID'),
            'client_secret' => env('IDENTITY_PORTAL_SECRET'),
            'redirect' => env('IDENTITY_PORTAL_CALLBACK_URL'),
            'scopes' => 'openid offline_access email profile groups',
            'home_route' => 'dashboard',
        ],
        'staff' => [
            'openid_configuration' => env('IDENTITY_OPENID_CONFIGURATION'),
            'client_id' => env('IDENTITY_STAFF_ID'),
            'client_secret' => env('IDENTITY_STAFF_SECRET'),
            'redirect' => env('IDENTITY_STAFF_CALLBACK_URL'),
            'scopes' => 'openid offline_access email profile groups',
            'home_route' => 'staff.dashboard',
        ],
    ],
    'hydra' => [
        'public' => env('HYDRA_PUBLIC_URL'),
        'local_public' => env('HYDRA_LOCAL_PUBLIC'), # For Dev purposes
        'admin' => env('HYDRA_ADMIN_URL')
    ],
    // Yubikey
    'yubikey' => [
        'client_id' => env('YUBICO_CLIENT_ID'),
        'secret_key' => env('YUBICO_SECRET_KEY'),
    ]
];
