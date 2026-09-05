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
    'fonnte' => [
        'token' => env('FONNTE_TOKEN'),
    ],

    // Peta layanan kota (CCTV & Wifi Gratis) dari API publik eManuntung Pemkot
    // Balikpapan. Tanpa token — koordinat acuan default = tengah RT-44.
    'emanuntung' => [
        'lat' => env('EMANUNTUNG_LAT', -1.2635),
        'lng' => env('EMANUNTUNG_LNG', 116.8942),
        // Server mengabaikan parameter radius-nya sendiri (selalu balas ~19 km),
        // jadi penyaringan jarak dilakukan di EManuntungService. Satuan: kilometer.
        'radius_km' => env('EMANUNTUNG_RADIUS_KM', 10),
    ],

];
