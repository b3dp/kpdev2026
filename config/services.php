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

    'recaptcha' => [
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'threshold' => env('RECAPTCHA_SCORE_THRESHOLD', 0.5),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'project_id' => env('GOOGLE_CLOUD_PROJECT_ID'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
        'key_public' => env('GOOGLE_MAPS_API_KEY_PUBLIC'),
    ],

    'google_drive' => [
        'auth_mode' => env('GOOGLE_DRIVE_AUTH_MODE', 'service_account'),
        'service_account_json_path' => env('GOOGLE_SERVICE_ACCOUNT_JSON_PATH', storage_path('app/private/google-service-account.json')),
        'oauth_client_id' => env('GOOGLE_DRIVE_OAUTH_CLIENT_ID'),
        'oauth_client_secret' => env('GOOGLE_DRIVE_OAUTH_CLIENT_SECRET'),
        'oauth_refresh_token' => env('GOOGLE_DRIVE_OAUTH_REFRESH_TOKEN'),
        'bagis_klasor_id' => env('GOOGLE_DRIVE_BAGIS_KLASOR_ID'),
        'ekayit_klasor_id' => env('GOOGLE_DRIVE_EKAYIT_KLASOR_ID'),
    ],

    'ga4' => [
        'measurement_id' => env('GA4_MEASUREMENT_ID'),
        'api_secret' => env('GA4_API_SECRET'),
    ],

    'google_ads' => [
        'tag_id' => env('GOOGLE_ADS_TAG_ID'),
    ],

    'zeptomail' => [
        'api_key' => env('ZEPTOMAIL_API_KEY'),
        'from_address' => env('ZEPTOMAIL_FROM_ADDRESS'),
        'from_name' => env('ZEPTOMAIL_FROM_NAME'),
    ],

    'haber_onay' => [
        'editor_id' => env('HABER_ONAY_EDITOR_ID', 1),
        'sms_dakika' => (int) env('HABER_ONAY_SMS_DAKIKA', 60),
    ],

    'iletisim_makinesi' => [
        'username' => env('ILETISIM_MAKINESI_USERNAME'),
        'password' => env('ILETISIM_MAKINESI_PASSWORD'),
        'customer_code' => env('ILETISIM_MAKINESI_CUSTOMER_CODE'),
        'api_key' => env('ILETISIM_MAKINESI_API_KEY'),
        'vendor_code' => env('ILETISIM_MAKINESI_VENDOR_CODE', 2),
        'originator_id' => env('ILETISIM_MAKINESI_ORIGINATOR_ID', 45605),
        'async_limit' => (int) env('ILETISIM_MAKINESI_ASYNC_LIMIT', 500),
        'validity_period' => (int) env('ILETISIM_MAKINESI_VALIDITY_PERIOD', 1440),
    ],

    'bagis' => [
        'test_mode' => (bool) env('BAGIS_TEST_ODEME_AKTIF', true),
        'test_cards' => [
            [
                'etiket' => 'Başarılı Visa',
                'kart_no' => '4111 1111 1111 1111',
                'sonuc' => 'basarili',
                'mesaj' => 'Test ödeme başarıyla tamamlandı.',
            ],
            [
                'etiket' => 'Başarılı Mastercard',
                'kart_no' => '5555 5555 5555 4444',
                'sonuc' => 'basarili',
                'mesaj' => 'Test ödeme başarıyla tamamlandı.',
            ],
            [
                'etiket' => 'Yetersiz Bakiye',
                'kart_no' => '4000 0000 0000 0002',
                'sonuc' => 'hatali',
                'mesaj' => 'Test kartı yetersiz bakiye senaryosuna düştü.',
            ],
            [
                'etiket' => 'Banka Reddi',
                'kart_no' => '4000 0000 0000 9995',
                'sonuc' => 'hatali',
                'mesaj' => 'Test kartı banka reddi senaryosuna düştü.',
            ],
        ],
    ],

];
