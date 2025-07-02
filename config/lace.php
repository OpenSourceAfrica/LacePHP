<?php

return [
    'boot' => [
        'timezone' => 'Africa/Lagos',
        'debug'        => env('LACE_APP_DEBUG', true),
        'show_blisters'=> env('LACE_APP_SHOW_BLISTERS', true),
    ],
    'database' => [
        'driver'        => env('DB_DRIVER', 'sqlite'),
        'sqlite' => [
            'database_file' => env('DB_FILE', __DIR__ . '/../database.sqlite')
        ],
        'mysql' => [
            'host'     => env('DB_HOST', '127.0.0.1'),
            'port'     => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'lace'),
            'username' => env('DB_USERNAME', 'lace'),
            'password' => env('DB_PASSWORD', ''),
            'charset'  => env('DB_CHARSET', 'utf8mb4'),
            'collation'=> env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        ],
    ],
    'logging' => [
        'enabled' => env('LACE_APP_LOGGING', true), // fallback to true
        'levels' => ['404', '401', '500'],     // optionally make this configurable
        'path' => 'shoebox/logs/lace.log',
    ],
    'sole_version' => 7,
    'base_url' => env('LACE_APP_BASE_URL', 'https://127.0.0.1'),
    'grip_level' => 'high',
    'paths' => [
        'vendor' => env('VENDOR_DIR', 'vendor'),
    ],
    'auth' => [
        'guard'  => env('AUTH_GUARD', 'token'),
        'tokens' => [
            env('TOKEN_SECRET1', 'secret123'),
            env('TOKEN_SECRET2', 'anotherSecret456'),
        ],
    ]
];