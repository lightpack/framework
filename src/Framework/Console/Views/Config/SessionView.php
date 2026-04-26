<?php

namespace Lightpack\Console\Views\Config;

class SessionView
{
    public static function getTemplate()
    {
        return <<<'PHP'
<?php

/**
 * ------------------------------------------------------------
 * Session settings.
 * ------------------------------------------------------------
 */

return [
    'session' => [
        'driver' => get_env('SESSION_DRIVER', 'file'),
        'name' => get_env('SESSION_NAME', 'lightpack_session'),
        'lifetime' => get_env('SESSION_LIFETIME', 7200), // Server-side inactivity timeout (seconds)
        'path' => get_env('SESSION_PATH', sys_get_temp_dir() . '/lightpack_sessions'), // File storage path
        'same_site' => get_env('SESSION_SAME_SITE', 'lax'),
        'https' => get_env('SESSION_HTTPS', false),
        'http_only' => get_env('SESSION_HTTP_ONLY', true),
        'cookie_path' => get_env('SESSION_COOKIE_PATH', '/'),
        'cookie_domain' => get_env('SESSION_COOKIE_DOMAIN', ''),
        'encrypt' => get_env('SESSION_ENCRYPT', false),
    ]
];
PHP;
    }
}
