<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Redis Connection Settings
    |--------------------------------------------------------------------------
    |
    | Configure your Redis connection settings here. These settings will be used
    | by the Redis client to establish connections to your Redis server.
    |
    */
    
    'default' => [
        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
        'port' => $_ENV['REDIS_PORT'] ?? 6379,
        'password' => $_ENV['REDIS_PASSWORD'] ?? null,
        'database' => $_ENV['REDIS_DB'] ?? 0,
        'timeout' => $_ENV['REDIS_TIMEOUT'] ?? 0.0,
        'read_timeout' => $_ENV['REDIS_READ_TIMEOUT'] ?? 0.0,
        'retry_interval' => $_ENV['REDIS_RETRY_INTERVAL'] ?? 0,
        'prefix' => $_ENV['REDIS_PREFIX'] ?? '',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Redis Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Redis cache settings. These will be used when the cache driver
    | is set to 'redis' in your cache configuration.
    |
    */
    
    'cache' => [
        'connection' => 'default',
        'prefix' => 'cache:',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Redis Session Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Redis session settings. These will be used when the session driver
    | is set to 'redis' in your session configuration.
    |
    */
    
    'session' => [
        'connection' => 'default',
        'prefix' => 'session:',
        'lifetime' => 7200, // 2 hours
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Redis Job Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Redis job queue settings. These will be used when the job engine
    | is set to 'redis' in your environment configuration.
    |
    */
    
    'jobs' => [
        'connection' => 'default',
        'prefix' => $_ENV['REDIS_JOB_PREFIX'] ?? 'jobs:',
        'database' => $_ENV['REDIS_JOB_DB'] ?? 0,
    ],
];
