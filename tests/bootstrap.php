<?php

/**
 * PHPUnit Test Bootstrap
 * 
 * This file ensures all tests can run in isolation by defining
 * required constants that may be needed across test suites.
 */

// Define framework constants if not already defined
if (!defined('DIR_CONFIG')) {
    define('DIR_CONFIG', __DIR__ . '/fixtures/config');
}

if (!defined('DIR_VIEWS')) {
    define('DIR_VIEWS', __DIR__ . '/fixtures/views');
}

if (!defined('APP_ENV')) {
    define('APP_ENV', 'testing');
}

if (!defined('DIR_STORAGE')) {
    define('DIR_STORAGE', __DIR__ . '/fixtures/storage');
}

// Create fixture directories if they don't exist
$fixtureDirectories = [
    __DIR__ . '/fixtures',
    __DIR__ . '/fixtures/config',
    __DIR__ . '/fixtures/views',
    __DIR__ . '/fixtures/storage',
    __DIR__ . '/fixtures/storage/logs',
];

foreach ($fixtureDirectories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Load Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';
