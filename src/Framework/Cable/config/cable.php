<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cable Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default cable driver that will be used for
    | real-time communication. Supported: "database", "redis"
    |
    */
    'driver' => get_env('CABLE_DRIVER', 'database'),
    
    /*
    |--------------------------------------------------------------------------
    | Database Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the database driver settings.
    |
    */
    'database' => [
        'table' => 'cable_messages',
        'cleanup_older_than' => 86400, // 24 hours
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Redis Driver Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the Redis driver settings.
    |
    */
    'redis' => [
        'prefix' => 'cable:',
        'ttl' => 86400, // 24 hours
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Client Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the client-side settings.
    |
    */
    'client' => [
        'poll_interval' => 3000, // milliseconds
        'reconnect_interval' => 5000, // milliseconds
        'max_reconnect_attempts' => 5,
    ],
];
