<?php

use App\Models\Uye;
use App\Models\User;
use App\Models\Yonetici;

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'yoneticiler'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'yoneticiler',
        ],

        'admin' => [
            'driver' => 'session',
            'provider' => 'yoneticiler',
        ],

        'uye' => [
            'driver' => 'session',
            'provider' => 'uyeler',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => User::class,
        ],

        'yoneticiler' => [
            'driver' => 'eloquent',
            'model' => Yonetici::class,
        ],

        'uyeler' => [
            'driver' => 'eloquent',
            'model' => Uye::class,
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        'yoneticiler' => [
            'provider' => 'yoneticiler',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],

        'uyeler' => [
            'provider' => 'uyeler',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 10,
            'throttle' => 10,
        ],
    ],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
