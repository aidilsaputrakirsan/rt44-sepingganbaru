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
    /*
     * Baca otomatis foto KK/KTP di Data Warga lewat OpenRouter (API OpenAI-compatible).
     * Setiap permintaan dipaksa ke penyedia Zero Data Retention tanpa pengumpulan data
     * (lihat App\Services\Ai\OpenRouterClient). Kosongkan API key → fitur scan disembunyikan.
     */
    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'base_url' => env('OPENROUTER_BASE_URL', 'https://openrouter.ai/api/v1'),
        'model' => env('OPENROUTER_MODEL', 'qwen/qwen3-vl-30b-a3b-instruct'),
        // Model yang bisa membaca gambar & punya penyedia ZDR; cadangan dicoba berurutan (pisahkan koma).
        'vision_model' => env('OPENROUTER_VISION_MODEL', 'qwen/qwen3-vl-30b-a3b-instruct'),
        'vision_fallbacks' => array_values(array_filter(array_map('trim', explode(',', (string) env('OPENROUTER_VISION_FALLBACKS', 'qwen/qwen3.5-9b'))))),
        // Mode pengembangan: hasil tiruan instan tanpa API & kuota.
        'fake' => (bool) env('OPENROUTER_FAKE', false),
        'timeout' => (int) env('OPENROUTER_TIMEOUT', 60),
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
