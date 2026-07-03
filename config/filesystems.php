<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
     * Disk used for user-facing media (avatars, gallery photos, branding
     * logo/favicon) that must be reachable over HTTP. Deliberately separate
     * from 'default': that disk is for private/internal storage and its
     * 'local' driver has no public URL, so using it here would silently
     * 404 uploaded images. Point this at 'r2' (with R2_* env vars set)
     * to move public media to object storage without touching any code.
     */
    'media_disk' => env('MEDIA_DISK', 'public'),

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
        public_path('storage') => storage_path('app/public'),
    ],

];
