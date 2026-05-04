<?php

return [
    'base_url' => env('LACE_APP_BASE_URL', 'https://127.0.0.1'),
    'grip_level' => 'high',
    'sole_version' => 8,
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
    'session' => [
        // 'file' | 'database' | 'cache'
        'driver'   => env('LACE_SESSION_DRIVER', 'file'),

        'name'     => env('LACE_SESSION_NAME', 'lacephp_session'),
        'lifetime' => env('LACE_SESSION_LIFETIME', 1200),
        'path'     => '/',
        'domain'   => '',
        'secure'   => env('LACE_SESSION_SECURE', true),
        'httponly' => true,
        'same_site'=> 'None',

        // FILE driver options
        'file' => [
            'path' => env('INSTEP_ENGINE_PATH', __DIR__ . '/../shoebox/sessions'),
        ],

        // DATABASE driver options
        'database' => [
            'table'    => 'sessions',
        ],

        // CACHE driver options
        'cache' => [
            'prefix' => 'session:',
            'ttl'    => env('LACE_SESSION_TTL', 1200),
        ],

        'sweep_enabled'   => true,
        'sweep_interval'  => 300,   // every 5 minutes max
        'sweep_max_files' => 500,   // delete up to 500 per sweep
    ],
    'cache' => [
        'driver'      => env('INSTEP_ENGINE', 'file'),
        'path'        => env('INSTEP_ENGINE_PATH', __DIR__ . '/../shoebox/cache'), // for file
        'default_ttl' => 3600,

        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'auth' => null,
            'db'   => 0,
            'prefix' => 'lace:',
            'default_ttl' => 3600,
        ],

        'memcache' => [
            'servers' => [
                ['127.0.0.1', 11211, 1], // host, port, weight (Memcached)
            ],
            'prefix' => 'lace:',
            'default_ttl' => 3600,
        ],
    ],
    'logging' => [
        'enabled' => env('LACE_APP_LOGGING', true),
        'levels' => ['404', '401', '500', 'info', 'error', 'debug'],
        'path' => 'shoebox/logs/lace.log',
    ],
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