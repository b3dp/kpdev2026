<?php
return [
    'datetime_format' => 'd.m.Y H:i:s',
    'date_format' => 'd.m.Y',

    'activity_resource' => \App\Filament\Resources\LogResource::class,
    'scoped_to_tenant' => false,
    'navigation_sort' => null,

    'resources' => [
        'enabled' => true,
        'log_name' => 'Kaynak',
        'logger' => \Z3d0X\FilamentLogger\Loggers\ResourceLogger::class,
        'color' => 'success',

        'exclude' => [
            //App\Filament\Resources\UserResource::class,
        ],
        'cluster' => null,
        'navigation_group' => 'Sistem',
    ],

    'access' => [
        'enabled' => true,
        'logger' => \Z3d0X\FilamentLogger\Loggers\AccessLogger::class,
        'color' => 'danger',
        'log_name' => 'Güvenlik',
    ],

    'notifications' => [
        'enabled' => true,
        'logger' => \Z3d0X\FilamentLogger\Loggers\NotificationLogger::class,
        'color' => null,
        'log_name' => 'Bildirim',
    ],

    'models' => [
        'enabled' => true,
        'log_name' => 'Model',
        'color' => 'warning',
        'logger' => \Z3d0X\FilamentLogger\Loggers\ModelLogger::class,
        'register' => [
            App\Models\Yonetici::class,
        ],
    ],

    'custom' => [
        // [
        //     'log_name' => 'Özel',
        //     'color' => 'primary',
        // ]
    ],
];
