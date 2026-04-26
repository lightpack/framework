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
        'lifetime' => get_env('SESSION_LIFETIME', 7200), // Inactivity timeout (seconds)
        'path' => get_env('SESSION_PATH'), // Optional: custom session storage path
        'same_site' => get_env('SESSION_SAME_SITE', 'lax'),
        'https' => get_env('SESSION_HTTPS', false),
        'http_only' => get_env('SESSION_HTTP_ONLY', true),
        'encrypt' => get_env('SESSION_ENCRYPT', false),
    ]
];
PHP;
    }
}
