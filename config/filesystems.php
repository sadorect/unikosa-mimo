<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => 'auto',
            'endpoint' => env('R2_ENDPOINT'),
            'url' => env('R2_URL'),
            'bucket' => env('R2_BUCKET'),
            'use_path_style_endpoint' => true,
            'throw' => false,
        ],

    ],

    'links' => [
        'public' => env('APP_URL').'/storage',
    ],

];
